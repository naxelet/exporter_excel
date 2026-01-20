<?php
class ImportIblockService
{
    private ?int $iblockId = null;
    private ?string $iblockCode = null;
	
    public function __construct(
        int|string $iblock
    ) {
        $resolve_iblock = $this->resolveIblock($iblock);
        $this->iblockId = (int)$resolve_iblock['ID'];
        $this->iblockCode = $resolve_iblock['CODE'] ?? '';
    }

    /**
     * Получение информации об инфоблоке
     */
    private function resolveIblock(int|string $identifier): array
    {
        if (is_numeric($identifier)) {
            $filter = ['ID' => (int) $identifier];
        } else {
            $filter = ['=CODE' => (string) $identifier];
        }

        $iblock = \Bitrix\Iblock\IblockTable::getRow([
            'select' => ['ID', 'CODE', 'NAME', 'IBLOCK_TYPE_ID'],
            'filter' => $filter,
        ]);

        if (!$iblock) {
            throw new \Exception('Инфоблок не найден');
            //throw new \InvalidArgumentException(Loc::getMessage('IBLOCK_NOT_FOUND'));
        }

        return $iblock;
    }

    /**
     * Импорт данных через генератор
     */
    public function import(iterable $rows_generator, callable $mapper, array $options = []): ImportResult
    {
        $import_result = new ImportResult();
        $batch_size = $options['batch_size'] ?? 50;
        $batch = [];

        foreach ($rows_generator as $index => $row) {
            try {
                $row_mapped = $mapper($row);
                $this->validateMappedImport($row_mapped);

                $batch[] = $row_mapped;
                $import_result->totalProcessed++;

                if (count($batch) >= $batch_size) {
                    $this->processBatch($batch, $import_result);
                    $batch = [];
                }

            } catch (ImportException $e) {
                $import_result->addError($index, $e);
            } catch (\Throwable $e) {
                $import_result->addError($index, new ImportException(
                    'Критическая ошибка импорта', // Loc::getMessage('IMPORT_CRITICAL_ERROR'),
                    ['exception' => $e],
                    $index
                ));
            }
        }

        // Обработка оставшихся данных
        if (!empty($batch)) {
            $this->processBatch($batch, $import_result);
        }

        return $import_result;
    }

    /**
     * Валидация данных импорта
     */
    private function validateMappedImport(array $mapped): void
    {
        if (!isset($mapped['element'])) {
            throw new \Exception('Отсутствуют данные элемента');
            //throw new ImportException(Loc::getMessage('VALIDATION_NO_ELEMENT_DATA'));
        }

        if (empty($mapped['element']['NAME'])) {
            throw new \Exception('Не указано название элемента');
            //throw new ImportException(Loc::getMessage('VALIDATION_NO_NAME'));
        }

        $validModes = ['create', 'update', 'create_or_update'];
        if (!in_array($mapped['mode'] ?? 'create_or_update', $validModes, true)) {
            throw new \Exception('Некорректный режим импорта');
            //throw new ImportException(Loc::getMessage('VALIDATION_INVALID_MODE'));
        }
    }

    /**
     * Обработка пачки элементов
     */
    private function processBatch(array $batch, ImportResult $result): void
    {
        foreach ($batch as $item) {
            try {
                $elementId = $this->processItem($item);

                if ($item['mode'] === 'update') {
                    $result->updated++;
                } else {
                    $result->created++;
                }

                $result->successIds[] = $elementId;

            } catch (ImportException $e) {
                $result->failed++;
                $result->addError($result->totalProcessed, $e);
            }
        }

        // Очистка кэша для экономии памяти
        /*if (count($this->propertyCache) > 1000) {
            $this->propertyCache = [];
        }*/
    }

    /**
     * Обработка одного элемента
     */
    private function processItem(array $item): int
    {
        $element = $item['element'];
        $properties = $item['properties'] ?? [];
        $mode = $item['mode'] ?? 'create_or_update';

        // Поиск существующего элемента
        $xmlId = $elementData['XML_ID'] ?? null;
        $existingId = null;

        if ($xmlId && $mode !== 'create') {
            $existingId = $this->findElementIdByXmlId($xmlId);
        }

        // Создание или обновление
        if ($existingId && $mode !== 'create') {
            return $this->updateElement($existingId, $elementData, $properties);
        }

        return $this->createElement($elementData, $properties);
    }

    /**
     * Создание элемента через D7 ORM
     */
    private function createElement(array $fields, array $properties): int
    {
        $element = new Iblock\ElementTable();

        // Подготовка полей
        $preparedFields = $this->prepareElementFields($fields);
        $preparedFields['IBLOCK_ID'] = $this->iblockId;

        // Транзакция для целостности данных
        $connection = Main\Application::getConnection();
        $connection->startTransaction();

        try {
            // Создание элемента
            $result = $element::add($preparedFields);

            if (!$result->isSuccess()) {
                throw new ImportException(
                    Loc::getMessage('ELEMENT_CREATE_ERROR'),
                    ['errors' => $result->getErrorMessages(), 'fields' => $preparedFields]
                );
            }

            $elementId = $result->getId();

            // Установка свойств
            if (!empty($properties)) {
                $this->setElementProperties($elementId, $properties);
            }

            // Установка разделов
            if (!empty($sections)) {
                $this->setElementSections($elementId, $sections);
            }

            $connection->commitTransaction();

            $this->log("Создан элемент #{$elementId}: {$preparedFields['NAME']}");
            return $elementId;

        } catch (\Throwable $e) {
            $connection->rollbackTransaction();
            throw new ImportException(
                Loc::getMessage('ELEMENT_CREATE_TRANSACTION_ERROR'),
                ['exception' => $e],
                0,
                $e
            );
        }
    }

    /**
     * Обновление элемента через D7 ORM
     */
    private function updateElement(int $elementId, array $fields, array $properties, array $sections): int
    {
        /*$element = new Iblock\ElementTable();

        // Подготовка полей
        $preparedFields = $this->prepareElementFields($fields);

        // Транзакция
        $connection = Main\Application::getConnection();
        $connection->startTransaction();

        try {
            // Обновление элемента
            $result = $element::update($elementId, $preparedFields);

            if (!$result->isSuccess()) {
                throw new ImportException(
                    Loc::getMessage('ELEMENT_UPDATE_ERROR'),
                    ['errors' => $result->getErrorMessages(), 'element_id' => $elementId]
                );
            }

            // Обновление свойств если указаны
            if (!empty($properties)) {
                $this->setElementProperties($elementId, $properties);
            }

            // Обновление разделов если указаны
            if (!empty($sections)) {
                $this->setElementSections($elementId, $sections, true);
            }

            $connection->commitTransaction();

            $this->log("Обновлен элемент #{$elementId}");
            return $elementId;

        } catch (\Throwable $e) {
            $connection->rollbackTransaction();
            throw new ImportException(
                Loc::getMessage('ELEMENT_UPDATE_TRANSACTION_ERROR'),
                ['element_id' => $elementId, 'exception' => $e],
                0,
                $e
            );
        }*/
    }

    /**
     * Получение ID инфоблока
     */
    public function getIblockId(): int
    {
        return $this->iblockId;
    }

    /**
     * Получение кода инфоблока
     */
    public function getIblockCode(): string
    {
        return $this->iblockCode;
    }
}