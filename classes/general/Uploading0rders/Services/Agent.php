<?php

namespace Uploading0rders\Services;

use \Bitrix\Main\IO\Directory;
use \Bitrix\Main\Config\Option;
use \Uploading0rders\ClientsHistoryExcel;
use \Uploading0rders\ImportIblockService;
use \Uploading0rders\Mapper\ColumnExcelMapper;
use \Uploading0rders\Mapper\UploadingOrderMapper;
use \Uploading0rders\Processor\InfoblockBatchProcessor;


class Agent
{
    const MODULE_ID = 'akatan.exporterexcel';

    public static function runImportFile(): string
    {
        try {
            $iblock_id = (int)trim(htmlspecialcharsbx(Option::get(static::MODULE_ID, 'IBLOCK_ID', '')));
            $update_existing = Option::get(static::MODULE_ID, 'UPDATE_EXISTING');
            $start_row = Option::get(static::MODULE_ID, 'START_ROW');
            $clear_columns = Option::get(static::MODULE_ID, 'CLEAR_COLUMNS');
            $clear_columns_index = Option::get(static::MODULE_ID, 'CLEAR_COLUMNS_INDEX');
            $clear_columns_num = Option::get(static::MODULE_ID, 'CLEAR_COLUMNS_NUM');
            $allowedExtensions = ['xml', 'xlsx', 'xls', 'csv'];
            $mode = ($update_existing === 'Y') ? 'create_or_update' : 'create';
            $filePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . static::MODULE_ID . '/test.xls';
            $inputFileName =  realpath($filePath);
            $log_module_dir = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $module_id . '/logs/';
            $log_path = $log_module_dir . 'import_' . date('Y-m-d') . '.log';
            $logger = new FileLogger($log_path);
            $logger->setLevel(\Psr\Log\LogLevel::DEBUG);
            $activeSheetIndex = 0;
            $settings = [
                'mode' => $mode,
            ];
            if (file_exists($inputFileName)) {
                $mapper_xml = new ColumnExcelMapper();
                $mapper_loading = new UploadingOrderMapper();
                $excel_file = new ClientsHistoryExcel($inputFileName, $activeSheetIndex, $mapper_xml);
                $excel_import = new ImportIblockService($iblock_id);
                $ib_processor = new InfoblockBatchProcessor($excel_import, $mapper_loading, $logger, $settings);

                if ($clear_columns === 'Y') {
                    $excel_file->clearColums($clear_columns_index, $clear_columns_num);
                }

                $result = $ib_processor->import($excel_file->getRows($start_row));

                Option::set(static::MODULE_ID, 'LAST_IMPORT_DATE', (new \DateTime())->format('Y-m-d H:i:s'));
                Option::set(static::MODULE_ID, 'LAST_IMPORT_FILE', $inputFileName);
                Option::set(static::MODULE_ID, 'LAST_IMPORT_COUNT', $result->getSuccessCount());
                Option::set(static::MODULE_ID, 'LAST_IMPORT_STATS', $result->getStatsString());

                if (!$result->isSuccess()) {}
            }
        } catch (\Exception $exception) {

        } finally {
            return '\Uploading0rders\Services\Agent::runImportFile();';
        }
    }

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