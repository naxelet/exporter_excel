<?php

namespace Uploading0rders\Services;

use \Bitrix\Main\IO\Directory;
use \Bitrix\Main\Config\Option;
use \Uploading0rders\ClientsHistoryExcel;
use \Uploading0rders\ImportIblockService;
use \Uploading0rders\Mapper\ColumnExcelMapper;
use \Uploading0rders\Mapper\UploadingOrderMapper;
use \Uploading0rders\Processor\InfoblockBatchProcessor;
use \Bitrix\Main\Diag\FileLogger;


class Agent
{
    const MODULE_ID = 'akatan.exporterexcel';

    public static function deleteModuleLoadingFiles(): string
    {
        static::deleteModuleCornerLoadingFiles();
        static::deleteModuleStaticLoadingFiles();

        return '\Uploading0rders\Services\Agent::deleteModuleLoadingFiles();';
    }

    private static function deleteModuleStaticLoadingFiles(): void
    {
        $path_upload_module = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . static::MODULE_ID . '/';

        static::removeAllDir($path_upload_module);

        /*$files_upload_module = glob($path_upload_module . '*');

        foreach ($files_upload_module as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        unset($path_upload_module, $files_upload_module);*/
        unset($path_upload_module);
    }

    private static function removeAllDir(string $dir, bool $is_nested = false): void
    {
        $includes = new \FilesystemIterator($dir);
        $exclude_dir = ['logs'];

        foreach ($includes as $include) {
            if (is_dir($include) && !is_link($include) && !in_array(basename($include), $exclude_dir)) {
                static::removeAllDir($include, true);
            } else {
                unlink($include);
            }
        }
        if ($is_nested) {
            rmdir($dir);
        }
    }

    private static function deleteModuleCornerLoadingFiles(): void
    {
        $res_module_files = \CFile::GetList(['FILE_SIZE' => 'desc'], ['MODULE_ID' => static::MODULE_ID]);
        while ($module_file = $res_module_files->GetNext()) {
            \CFile::Delete($module_file['ID']);
        }
    }
}