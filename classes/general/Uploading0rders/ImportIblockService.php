<?php

namespace Uploading0rders;

use Bitrix\Main\DB\TransactionException;
use \Uploading0rders\Error\ImportException;

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
     * @throws ImportException | \Throwable
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
            throw new ImportException(
                'Ошибка при добавлении элемента.', //Loc::getMessage('ELEMENT_CREATE_ERROR'),
                ['errors' => $element->LAST_ERROR, 'fields' => $preparedFields]
            );
        }
//            log("Создан элемент #{$elementId}: {$preparedFields['NAME']}");
        return (int) $element_id;
    }

    /**
     * Обновление элемента
     * @array  $fields Параметры элемента
     * @throws \ImportException
     */
    public function updateElement(
        array $fields
    ): int
    {
        static::validatePropsImport($fields);
        $element = new \CIBlockElement();
        $element_id = null;

        // Подготовка полей
        $preparedFields = $this->prepareElementFields($fields);
        $preparedFields['IBLOCK_ID'] = $this->iblockId;
        $arSelect = ['ID', 'NAME', 'CODE'];
        $arFilter = ['IBLOCK_ID' => IntVal($preparedFields['IBLOCK_ID']), 'CODE' => $preparedFields['CODE']];
        $res_element = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);

        if ($res_element->SelectedRowsCount() > 0) {
            while($fields_element = $res_element->GetNext())
            {
                if (!$element->Update($fields_element['ID'], $preparedFields)) {
                    throw new ImportException(
                        'Ошибка при обновлении элемента.' . $preparedFields['CODE'], //Loc::getMessage('ELEMENT_UPDATE_ERROR'),
                        ['errors' => $element->LAST_ERROR, 'fields' => $preparedFields]
                    );
                }
                return (int) $fields_element['ID'];
            }
        }
        throw new ImportException(
            'Элемент не найден. CODE: ' . $preparedFields['CODE'], //Loc::getMessage('ELEMENT_EMPTY_UPDATE_ERROR'),
            ['errors' => $element->LAST_ERROR, 'fields' => $preparedFields]
        );
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