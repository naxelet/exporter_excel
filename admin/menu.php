<?php
use Bitrix\Main\Localization\Loc;

AddEventHandler("main", "OnBuildGlobalMenu", "setExcelImporterMenu");

function setExcelImporterMenu(&$aGlobalMenu, &$aModuleMenu)
{
    $module_id = 'akatan.excel_importer';
    $arMenu = [];
    // проверка доступа к модулю
    if ($GLOBALS['APPLICATION']->GetGroupRight($module_id) < 'S') {
        $GLOBALS['APPLICATION']->AuthForm(Loc::getMessage('ACCESS_DENIED'));
    } else {
        $arMenu = [
            'menu_id' => 'global_menu_akatan_item',
            'title' => Loc::getMessage('EXCEL_IMPORTER_GLOBAL_MENU_ITEM_TITLE'),
            'text' => Loc::getMessage('EXCEL_IMPORTER_GLOBAL_MENU_ITEM_TEXT'),
            'sort' => 100,
            'items_id' => 'global_menu_akatan_item_general',
            'icon' => 'util_menu_icon',
            'url' => 'akatan.excel_importer.menu.php?lang=' . LANGUAGE_ID,
        ];
        $aGlobalMenu['global_menu_akatan'] = [
            'menu_id' => 'global_menu_akatan',
            'title' => Loc::getMessage('EXCEL_IMPORTER_GLOBAL_MENU_TITLE'),
            'text' => Loc::getMessage('EXCEL_IMPORTER_GLOBAL_MENU_TEXT'),
            'sort' => 1000,
            'items_id' => 'global_menu_akatan_items',
            'icon' => 'clouds_menu_icon',
            'items' => [
                $module_id => $arMenu
            ]
        ];
    }

}