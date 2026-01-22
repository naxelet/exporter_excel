<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Main\Event;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Main\Localization\Loc;

require_once(__DIR__ . '/autoload.php');

global $USER;

Loc::loadMessages(__FILE__);
Loader::registerAutoLoadClasses(
    'akatan',
    []
);

class AkatanExcelImporter
{
    const MODULE_ID = 'akatan.excel_importer';
    private static $siteId = null;
}