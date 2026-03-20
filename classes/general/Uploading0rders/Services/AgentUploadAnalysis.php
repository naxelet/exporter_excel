<?php

namespace Uploading0rders\Services;

use \Bitrix\Main\IO\Directory;
use \Bitrix\Main\Config\Option;
use \Uploading0rders\ClientsHistoryExcel;
use \Uploading0rders\ImportIblockService;
use \Uploading0rders\Mapper\ColumnExcelAnalysisMapper;
use \Uploading0rders\Mapper\UploadingOrderAnalysisMapper;
use \Uploading0rders\Processor\InfoblockBatchProcessor;
use \Bitrix\Main\Diag\FileLogger;


class AgentUploadAnalysis extends AgentUpload
{

    public static function runImportFile(): string
    {
        try {
            $iblock_id = (int)trim(htmlspecialcharsbx(Option::get(static::MODULE_ID, 'IBLOCK_ID', '')));
            $update_existing = Option::get(static::MODULE_ID, 'UPDATE_EXISTING_ANALYSIS');
            $start_row = Option::get(static::MODULE_ID, 'START_ROW_ANALYSIS');
            $clear_columns = Option::get(static::MODULE_ID, 'CLEAR_COLUMNS_ANALYSIS');
            $clear_columns_index = Option::get(static::MODULE_ID, 'CLEAR_COLUMNS_INDEX_ANALYSIS');
            $clear_columns_num = Option::get(static::MODULE_ID, 'CLEAR_COLUMNS_NUM_ANALYSIS');
            $file_path = Option::get(static::MODULE_ID, 'FILL_PATH_ANALYSIS');
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
            ];


            $logger->debug(
                "ImportAgentAnalysisStart: \n
                FILE_PATH: {FILE_PATH}\n
                INPUT_FILE_NAME: {INPUT_FILE_NAME}\n
                FILE_INFO: {FILE_INFO}\n
                IS_EXIST: {IS_EXIST}\n
                IN_ARRAY: {IN_ARRAY}\n",
                [
                    'FILE_PATH' => $file_path,
                    'INPUT_FILE_NAME' => $input_file_name,
                    'FILE_INFO' => $file_info,
                    'IS_EXIST' => file_exists($input_file_name) ? 'Y' : 'N',
                    'IN_ARRAY' => in_array(strtolower($file_info['extension']), static::ALLOWED_EXTENSIONS) ? 'Y' : 'N'
                ]
            );
            if (file_exists($input_file_name) && in_array(strtolower($file_info['extension']), static::ALLOWED_EXTENSIONS)) {
                $logger->debug(
                    "ImportAgentAnalysisExist: \n
                    IS_EXIST: {IS_EXIST}\n
                    IN_ARRAY: {IN_ARRAY}\n",
                    [
                        'IS_EXIST' => file_exists($input_file_name) ? 'Y' : 'N',
                        'IN_ARRAY' => in_array(strtolower($file_info['extension']), static::ALLOWED_EXTENSIONS) ? 'Y' : 'N'
                    ]
                );
                $mapper_xml = new ColumnExcelAnalysisMapper();
                $logger->debug(
                    "ImportAgentAnalysisExistMapperXML: \n
                    mapper_xml: {mapper_xml}\n",
                    [
                        'mapper_xml' => $mapper_xml,
                    ]
                );
                $mapper_loading = new UploadingOrderAnalysisMapper();
                $logger->debug(
                    "ImportAgentAnalysisExistMapperLoading: \n
                    mapper_loading: {mapper_loading}\n",
                    [
                        'mapper_loading' => $mapper_loading,
                    ]
                );
                $excel_file = new ClientsHistoryExcel($input_file_name, $active_sheet_index, $mapper_xml);
                $excel_import = new ImportIblockService($iblock_id);
                $ib_processor = new InfoblockBatchProcessor($excel_import, $mapper_loading, $logger, $settings);

                $logger->debug(
                    "ImportAgentAnalysisFileExists: \n
                    mapper_xml: {mapper_xml}\n
                    mapper_loading: {mapper_loading}\n
                    excel_file: {excel_file}",
                    [
                        'mapper_xml' => $mapper_xml,
                        'mapper_loading' => $mapper_loading,
                        'excel_file' => $excel_file,
                    ]
                );

                if ($clear_columns === 'Y') {
                    $excel_file->clearColums($clear_columns_index, $clear_columns_num);
                }

                $result = $ib_processor->import($excel_file->getRows($start_row));

                Option::set(static::MODULE_ID, 'LAST_IMPORT_ANALYSIS_DATE', (new \DateTime())->format('Y-m-d H:i:s'));
                Option::set(static::MODULE_ID, 'LAST_IMPORT_ANALYSIS_FILE', $input_file_name);
                Option::set(static::MODULE_ID, 'LAST_IMPORT_ANALYSIS_COUNT', $result->getSuccessCount());
                Option::set(static::MODULE_ID, 'LAST_IMPORT_ANALYSIS_STATS', $result->getStatsString());

                if (!$result->isSuccess()) {}
            }
        } catch (\Exception $exception) {
            $logger->error(
                "Ошибка AgentUploadAnalysis : <br>
                message: {message}<br>
                context: {context}<br>",
                [
                    'message' => $exception->getMessage(),
                    'context' => $exception->getContext(),
                ]
            );
        } finally {
            return '\Uploading0rders\Services\AgentUploadAnalysis::runImportFile();';
        }
    }
}