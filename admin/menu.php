<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$MODULE_ID = 'akatan.exporterexcel';

if ($GLOBALS['APPLICATION']->GetGroupRight($MODULE_ID) > 'D') {
    if (!CModule::IncludeModule($MODULE_ID)) {
        return false;
    }
    //$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/service/' . $MODULE_ID . '/menu.css');
    $aMenu = array(
        'parent_menu' => 'global_menu_services',
        //'section' => $MODULE_ID,
        'sort' => 100,
        'title' => Loc::getMessage('EXCEL_IMPORTER_GLOBAL_MENU_ITEM_TITLE'),
        'text' => Loc::getMessage('EXCEL_IMPORTER_GLOBAL_MENU_ITEM_TEXT'),
        "items_id" => "menu_akatan_item",
        "icon" => "util_menu_icon",
    );
    // дочерния ветка меню
        $aMenu["items"][] =  array(
            // название подпункта меню
            'text' => 'Страница модуля',
            // ссылка для перехода
            'url' => 'akatan.exporterexcel__general.php?lang=' . LANGUAGE_ID
        );
    // дочерния ветка меню
    $aMenu["items"][] = [
        // название подпункта меню
        'text' => 'Админка модуля',
        // ссылка для перехода
        'url' => 'settings.php?lang=ru&mid=' . $MODULE_ID
    ];
    return $aMenu;
}
$GLOBALS['APPLICATION']->AuthForm(Loc::getMessage('ACCESS_DENIED'));
return false;