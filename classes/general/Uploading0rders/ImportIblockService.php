<?php

namespace Uploading0rders;

use Bitrix\Main\DB\TransactionException;

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
     * Валидация данных импорта
     * @throws \Exception
     */
    private static function validatePropsImport(array $props): void
    {
        if (!isset($props['PROPERTY_VALUES'])) {
            throw new \Exception('Отсутствуют данные параметров');
            //throw new ImportException(Loc::getMessage('VALIDATION_NO_ELEMENT_DATA'));
        }

        if (empty($props['NAME'])) {
            throw new \Exception('Не указано название элемента');
            //throw new ImportException(Loc::getMessage('VALIDATION_NO_NAME'));
        }
    }

    /**
     * Создает новый элемент
     * @array  $fields Параметры элемента
     * @throws TransactionException | \Throwable
     */
    public function createElement(
        array $fields
    ): int
    {
        static::validatePropsImport($fields);
        $element = new \CIBlockElement();

        // Подготовка полей
        $preparedFields = $this->prepareElementFields($fields);
        $preparedFields['IBLOCK_ID'] = $this->iblockId;

        // Создание элемента
        $element_id = $element->Add($preparedFields);

        if (!$element_id) {
            throw new \Exception('Ошибка при добавлении элемента: ' . $element->LAST_ERROR);
//                throw new ImportException(
//                    Loc::getMessage('ELEMENT_CREATE_ERROR'),
//                    ['errors' => $result->getErrorMessages(), 'fields' => $preparedFields]
//                );
        }
//            $this->log("Создан элемент #{$elementId}: {$preparedFields['NAME']}");
        return (int) $element_id;
    }

    /**
     * Обновление элемента через D7 ORM
     */
    private function updateElement(
        array $fields
    ): int
    {
        return 0;
    }

    /**
     * Подготовка полей элемента
     */
    private function prepareElementFields(array $fields): array
    {
        $defaults = [
            'CODE' => '',
        ];

        $result = array_merge($defaults, $fields);

        // Генерация символьного кода
        if (empty($result['CODE']) && !empty($result['NAME'])) {
            $result['CODE'] = $this->generateCode($result['NAME']);
        }

        return $result;
    }

    /**
     * Создает код из строки
     */
    protected function generateCode(string $value): string
    {
        return \CUtil::translit(
            $value,
            'ru',
            [
                'replace_space' => '-',
                'replace_other' => '-',
                'delete_repeat_replace' => true
            ]
        );
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