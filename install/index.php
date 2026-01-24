<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\Application;
use \Bitrix\Main\Loader;
use \Bitrix\Main\IO\Directory;
use \Bitrix\Main\Config\Option;
use \Bitrix\Iblock\IblockTable;
use \Bitrix\Iblock\PropertyTable;

Loc::loadMessages(__FILE__);

class akatan_exporter_excel extends CModule
{
    public string $MODULE_ID = 'akatan.exporter_excel';
    public string $MODULE_VERSION;
    public string $MODULE_VERSION_DATE;
    public string $MODULE_NAME;
    public string $MODULE_DESCRIPTION;
    public string $PARTNER_NAME;
    public string $PARTNER_URI;
    public string $IBLOCK_TYPE_ID = 'services';
    public string $IBLOCK_CODE = 'uploading_order';

    public function __construct()
    {
        include __DIR__ . '/version.php';

        if (isset($arModuleVersion['VERSION'], $arModuleVersion['VERSION_DATE']))
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('AKATAN_EXCEL_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('AKATAN_EXCEL_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('AKATAN_EXCEL_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('AKATAN_EXCEL_PARTNER_URI');
    }

    /**
     * Установка модуля
     * @return void
     */
    public function DoInstall(): void
    {
        global $USER, $APPLICATION;

        if (!$USER->IsAdmin())
        {
            return;
        }

        if ($this->isVersionD7()) {
            ModuleManager::registerModule($this->MODULE_ID);

            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();

            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('AKATAN_EXCEL_INSTALL_TITLE'),
                __DIR__ . '/step.php'
            );
        } else {
            $APPLICATION->ThrowException(Loc::getMessage('AKATAN_EXCEL_INSTALL_ERROR_VERSION'));
        }
    }

    /**
     * Удаление модуля
     * @return void
     */
    public function DoUninstall(): void
    {
        global $USER;

        if (!$USER->IsAdmin())
        {
            return;
        }

//        $this->UnInstallFiles();
//        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * Работа с базой данных
     * @return boolean
     */
    public function InstallDB(): bool
    {
        Loader::includeModule('iblock');

        // Создаем тип инфоблока, если не существует
        $iblockType = new \CIBlockType;

        $arFields = [
            'ID' => $this->IBLOCK_TYPE_ID,
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => [
                'ru' => [
                    'NAME' => 'Служебные',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Элементы'
                ],
                'en' => [
                    'NAME' => 'Services',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements'
                ]
            ]
        ];

        if (!$iblockType->GetByID($this->IBLOCK_TYPE_ID)->Fetch()) {
            $iblockType->Add($arFields);
        }

        // Создаем инфоблок
        $iblock = new \CIBlock;
        $arFields = [
            'ACTIVE' => 'Y',
            'NAME' => Loc::getMessage('AKATAN_EXCEL_IBLOCK_NAME'),
            'CODE' => $this->IBLOCK_CODE,
            'IBLOCK_TYPE_ID' => $this->IBLOCK_TYPE_ID,
            'SITE_ID' => ['s1'],
            'SORT' => 100,
            'GROUP_ID' => ['2' => 'R'],
            'VERSION' => 2,
            'LIST_MODE' => 'C',
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
            'WORKFLOW' => 'N',
            'BIZPROC' => 'N',
            'SECTION_CHOOSER' => 'L',
            'LIST_PAGE_URL' => '',
            'SECTION_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'DESCRIPTION_TYPE' => 'text',
            'DESCRIPTION' => 'Инфоблок для хранения данных выгрузки',
            'XML_ID' => $this->IBLOCK_CODE,
        ];

        $iblockId = $iblock->Add($arFields);

        if ($iblockId) {
            // Сохраняем ID инфоблока в настройках модуля
            Option::set($this->MODULE_ID, 'IBLOCK_ID', $iblockId);

            // Создаем пользовательские свойства
            $this->createProperties($iblockId);
        }

        return true;
    }

    /**
     *
     * @param $iblockId Id инфоблока
     * @return void
     */
    private function createProperties(string|int $iblockId): void
    {
        $properties = [
            'BY_DATE' => [
                'NAME' => 'Дата',
                'SORT' => 100,
                'PROPERTY_TYPE' => 'S',
                'USER_TYPE' => 'DateTime',
                'IS_REQUIRED' => 'Y'
            ],
            'COUNTERPARTY' => [
                'NAME' => 'Контрагент',
                'SORT' => 200,
                'PROPERTY_TYPE' => 'S',
                'IS_REQUIRED' => 'N'
            ],
            'ARTICLE' => [
                'NAME' => 'Артикул',
                'SORT' => 300,
                'PROPERTY_TYPE' => 'S',
                'IS_REQUIRED' => 'N'
            ],
            'NOMENCLATURE' => [
                'NAME' => 'Номенклатура',
                'SORT' => 400,
                'PROPERTY_TYPE' => 'S',
                'IS_REQUIRED' => 'N'
            ],
            'CHAR_NOMENCLATURE' => [
                'NAME' => 'Характеристика номенклатуры',
                'SORT' => 500,
                'PROPERTY_TYPE' => 'S',
                'IS_REQUIRED' => 'N'
            ],
            'MOTION_DOCUMENT' => [
                'NAME' => 'Документ движения',
                'SORT' => 600,
                'PROPERTY_TYPE' => 'S',
                'IS_REQUIRED' => 'N'
            ],
            'QUANTITY' => [
                'NAME' => 'Количество',
                'SORT' => 700,
                'PROPERTY_TYPE' => 'N',
                'IS_REQUIRED' => 'N'
            ],
            'AMOUNT' => [
                'NAME' => 'Сумма',
                'SORT' => 800,
                'PROPERTY_TYPE' => 'N',
                'IS_REQUIRED' => 'N'
            ]
        ];

        foreach ($properties as $code => $property) {
            $propertyFields = [
                'NAME' => $property['NAME'],
                'ACTIVE' => 'Y',
                'SORT' => $property['SORT'],
                'CODE' => $code,
                'PROPERTY_TYPE' => $property['PROPERTY_TYPE'],
                'IBLOCK_ID' => $iblockId,
                'IS_REQUIRED' => $property['IS_REQUIRED'],
                'MULTIPLE' => 'N'
            ];

            if (isset($property['USER_TYPE'])) {
                $propertyFields['USER_TYPE'] = $property['USER_TYPE'];
            }

            $iblockProperty = new \CIBlockProperty;
            $iblockProperty->Add($propertyFields);
        }
    }

    /**
     * Работа с базой данных
     * @param array $arParams
     * @return boolean
     */
    public function UnInstallDB(array $arParams = []): bool
    {
        Loader::includeModule('iblock');

        $iblockId = Option::get($this->MODULE_ID, 'IBLOCK_ID');

        if ($iblockId && !$arParams['savedata']) {
            // Удаляем инфоблок со всеми данными
            \CIBlock::Delete($iblockId);

            // Удаляем тип инфоблока
            \CIBlockType::Delete($this->IBLOCK_TYPE_ID);
        }

        // Удаляем настройки модуля
        Option::delete($this->MODULE_ID);

        return true;
    }

    /**
     * Работа с файлами
     * @return boolean
     */
    public function InstallFiles(): bool
    {
        CopyDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
            true, true
        );
        return true;
    }

    /**
     * Работа с файлами
     * @return boolean
     */
    public function UnInstallFiles(): bool
    {
        DeleteDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin'
        );
        return true;
    }

    public function InstallEvents(): bool
    {
        return true;
    }

    public function UnInstallEvents(): bool
    {
        return true;
    }

    /**
     * Проверяет поддержку D7 ядра
     * @return mixed
     */
    private function isVersionD7(): mixed
    {
        return CheckVersion(
            ModuleManager::getVersion('main'),
            '14.00.00'
        );
    }
}