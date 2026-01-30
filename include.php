<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Main\Event;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\EventManager;

$module_id = 'akatan.exporterexcel';
$modulePath = dirname(__DIR__ . '/' . $module_id);
$autoloadPath = realpath($modulePath . '/vendor/autoload.php');

if (file_exists($autoloadPath)) {
    require_once($autoloadPath);
}

global $USER;

Loc::loadMessages(__FILE__);
Loader::registerAutoLoadClasses(
    'akatan',
    [
    ]
);

// Регистрируем обработчики событий
$eventManager = EventManager::getInstance();

// Обработчик для очистки загруженных файлов
$eventManager->addEventHandler('main', 'OnBeforeProlog', function() use ($module_id) {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $module_id . '/';
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        $now = time();
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $file_path = $upload_dir . $file;
                if (is_file($file_path) && ($now - filemtime($file_path)) > 3600) {
                    // Удаляем файлы старше 1 часа
                    unlink($file_path);
                }
            }
        }
    }
});

// Обработчик для проверки наличия библиотеки PhpSpreadsheet
$eventManager->addEventHandler('main', 'OnBeforeModuleInstall', function($moduleId, $arParams) use (
    $module_id, $modulePath, $autoloadPath
) {
    if ($moduleId === $module_id) {
        if (!file_exists($autoloadPath)) {
            throw new \Exception('Внимание: Библиотека PhpSpreadsheet не установлена для модуля ' . $module_id, $module_id);
        }
    }
});