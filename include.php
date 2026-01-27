<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Main\Event;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Main\Localization\Loc;

//require_once(__DIR__ . '/autoload.php');

global $USER;

Loc::loadMessages(__FILE__);
Loader::registerAutoLoadClasses(
    'akatan',
    []
);

class AkatanExporterExcel
{
    const MODULE_ID = 'akatan.exporterexcel';
    private static $siteId = null;
}