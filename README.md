# exporter_excel
Пример использования
``` php
use \Uploading0rders\ClientsHistoryExcel;
use \Uploading0rders\ImportIblockService;
use \Uploading0rders\Mapper\ColumnExcelMapper;
use \Uploading0rders\Mapper\UploadingOrderMapper;
use \Uploading0rders\Processor\InfoblockBatchProcessor;

$inputFileName =  realpath(__DIR__ . '/../upload/clients-history/test.xls');
$logPath = realpath(__DIR__ . '/../upload/logs/import_' . date('Y-m-d') . '.log');
$activeSheetIndex = 0;
$settings = [
    'mode' => 'create',
];
$mapper_xml = new ColumnExcelMapper();
$mapper_loading = new UploadingOrderMapper();

$excel_file = new ClientsHistoryExcel($inputFileName, $activeSheetIndex, $mapper_xml);
$excel_import = new ImportIblockService(39);
$ib_processor = new InfoblockBatchProcessor($excel_import, $mapper_loading, $settings);
$ib_processor->import($excel_file->getRows(605));

echo '<pre>' . print_r($excel_file->getFileStatistics(),true) . '</pre>';
echo '<pre>' . print_r($excel_import->getIblockId(),true) . '</pre>';
echo '<pre>' . print_r($excel_import->getIblockCode(),true) . '</pre>';
foreach ($excel_file->getRows(605) as $index => $row) {
    echo '<pre>' . print_r($row, true) . '</pre><br>';
}
```