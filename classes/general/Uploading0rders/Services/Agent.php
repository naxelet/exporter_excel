<?php

namespace Uploading0rders\Services;

use \Bitrix\Main\IO\Directory;


class Agent
{
    const MODULE_ID = 'akatan.exporterexcel';

    public static function deleteModuleLoadingFiles(): string
    {
        $path_upload_module = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . static::MODULE_ID . '/';

        $files_upload_module = glob($path_upload_module . '*');

        foreach ($files_upload_module as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        unset($path_upload_module, $files_upload_module);

        return '\Uploading0rders\Services\Agent::deleteModuleLoadingFiles();';
    }
}