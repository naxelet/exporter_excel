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
use \Bitrix\Main\SiteTable;

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
    public string $MODULE_GROUP_RIGHTS;

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
        $this->MODULE_GROUP_RIGHTS = 'N';
    }

    /**
     * Установка модуля
     * @return void
     */
    public function DoInstall(): void
    {
        global $USER, $APPLICATION;

//        $MODULE_RIGHT = $APPLICATION->GetGroupRight($this->MODULE_ID);
//        if ($MODULE_RIGHT>="W") {}
        if (!$USER->IsAdmin())
        {
            $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
        }

        if (
            $this->isVersionPHP() &&
            $this->isVersionD7()
        ) {
            $context = Application::getInstance()->getContext();
            $request = $context->getRequest();
            $session = Application::getInstance()->getSession();

            // не существует или меньше 2
            if ($request['step'] < 2) {


                $APPLICATION->IncludeAdminFile(
                    Loc::getMessage('AKATAN_EXCEL_INSTALL_TITLE_STEP_1'),
                    __DIR__ . '/step1.php'
                );
            }
            if ($request['step'] == 2) {
                // регистрируем модуль
                // теперь можно использовать неймспейсы
                ModuleManager::registerModule($this->MODULE_ID);

                $this->InstallDB();
                $this->InstallEvents();
                $this->InstallFiles();

                $APPLICATION->IncludeAdminFile(
                    Loc::getMessage('AKATAN_EXCEL_INSTALL_TITLE_STEP_2'),
                    __DIR__ . '/step2.php'
                );
            }
//            if ($request->getPost('install') !== null) {
//                $selectedSites = $request->getPost('selected_sites');
//                if (!empty($selectedSites)) {
//                    $session->set('akatan_excel_selected_sites', $selectedSites);
//                    //Option::set($this->MODULE_ID, 'SELECTED_SITES', serialize($selectedSites));
//                }
//            }
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
        global $USER, $APPLICATION;

        if (!$USER->IsAdmin())
        {
            $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
        }
        $request = Application::getInstance()->getContext()->getRequest();
        $step = (int)$request->get('step');

        if ($step < 2) {
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('AKATAN_EXCEL_UNINSTALL_TITLE'),
                __DIR__ . '/unstep1.php'
            );
        } elseif ($step == 2) {
            $this->UnInstallDB([
                'savedata' => $request->get('savedata') !== 'Y'
            ]);
            $this->UnInstallEvents();
            $this->UnInstallFiles();

            ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('AKATAN_EXCEL_UNINSTALL_TITLE'),
                __DIR__ . '/unstep2.php'
            );
        }
    }

    /**
     * Работа с базой данных
     * @return boolean
     */
    public function InstallDB(): bool
    {
        Loader::includeModule('iblock');

        // Получаем выбранные сайты из настроек
        $selectedSites = Option::get($this->MODULE_ID, 'SELECTED_SITES', '');
        if (!$selectedSites) {
            // Если сайты не выбраны, используем все активные сайты
            $sites = $this->getAllSites();
            $selectedSites = array_keys($sites);
        }

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
            'SITE_ID' => $selectedSites,
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

    private function getAllSites()
    {
        $sites = [];

        $dbSites = SiteTable::getList([
            'filter' => ['ACTIVE' => 'Y'],
            'order' => ['SORT' => 'ASC']
        ]);

        while ($site = $dbSites->fetch()) {
            $sites[$site['LID']] = $site['NAME'] . ' [' . $site['LID'] . ']';
        }

        return $sites;
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

    private function isVersionPHP(): mixed
    {
        return version_compare(PHP_VERSION, '8.0.0') >= 0;
    }

    /**
     *
     * @return array[]
     */
//    private function GetModuleRightList() {
//        return [
//            'reference_id' => ['D', 'K', 'S', 'W'],
//            'reference' => [
//                '[D] ' . Loc::getMessage('AKATAN_EXCEL_DENIED'),
//                '[K] ' . Loc::getMessage('AKATAN_EXCEL_READ_COMPONENT'),
//                '[S] ' . Loc::getMessage('AKATAN_EXCEL_WRITE_SETTINGS'),
//                '[W] ' . Loc::getMessage('AKATAN_EXCEL_FULL'),
//            ]
//        ];
//    }
}