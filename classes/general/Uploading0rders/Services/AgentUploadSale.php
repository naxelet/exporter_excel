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


class AgentUploadSale extends AgentUpload
{

    public static function runImportFile(): string
    {
        try {
            $iblock_id = (int)trim(htmlspecialcharsbx(Option::get(static::MODULE_ID, 'IBLOCK_ID', '')));
            $update_existing = Option::get(static::MODULE_ID, 'UPDATE_EXISTING_SALE');
            $skip_errors_sale = Option::get(static::MODULE_ID, 'SKIP_ERRORS_SALE');
            $start_row = Option::get(static::MODULE_ID, 'START_ROW_SALE');
            $clear_columns = Option::get(static::MODULE_ID, 'CLEAR_COLUMNS_SALE');
            $clear_columns_index = Option::get(static::MODULE_ID, 'CLEAR_COLUMNS_INDEX_SALE');
            $clear_columns_num = Option::get(static::MODULE_ID, 'CLEAR_COLUMNS_NUM_SALE');
            $file_path = Option::get(static::MODULE_ID, 'FILL_PATH_SALE');
            $active_sheet_index = 0;

            $mode = ($update_existing === 'Y') ? 'create_or_update' : 'create';
            $input_file_name =  realpath($file_path);
            $file_info = pathinfo($input_file_name);
            $log_module_dir = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . static::MODULE_ID . '/logs/';
            $log_path = $log_module_dir . 'import_' . date('Y-m-d') . '.log';
            $logger = new FileLogger($log_path);
            $logger->setLevel(\Psr\Log\LogLevel::DEBUG);
            $settings = [
                'mode' => $mode,
                'skip_errors' => ($skip_errors_sale === 'Y'),
            ];

            if (file_exists($input_file_name) && in_array(strtolower($file_info['extension']), static::ALLOWED_EXTENSIONS)) {
                $mapper_xml = new ColumnExcelMapper();
                $mapper_loading = new UploadingOrderMapper();
                $excel_file = new ClientsHistoryExcel($input_file_name, $active_sheet_index, $logger, $mapper_xml);
                $excel_import = new ImportIblockService($iblock_id);
                $ib_processor = new InfoblockBatchProcessor($excel_import, $mapper_loading, $logger, $settings);

                if ($clear_columns === 'Y') {
                    $excel_file->clearColums($clear_columns_index, $clear_columns_num);
                }

                $result = $ib_processor->import($excel_file->getRows($start_row));

                Option::set(static::MODULE_ID, 'LAST_IMPORT_SALE_DATE', (new \DateTime())->format('Y-m-d H:i:s'));
                Option::set(static::MODULE_ID, 'LAST_IMPORT_SALE_FILE', $input_file_name);
                Option::set(static::MODULE_ID, 'LAST_IMPORT_SALE_COUNT', $result->getSuccessCount());
                Option::set(static::MODULE_ID, 'LAST_IMPORT_SALE_STATS', $result->getStatsString());

                if (!$result->isSuccess()) {}
            }
        } catch (\Exception $exception) {
            $logger->error("Ошибка: " . $exception->getMessage(), [
                'context' => $exception->getContext(),
            ]);
        } finally {
            return '\Uploading0rders\Services\AgentUploadSale::runImportFile();';
        }
    }
}