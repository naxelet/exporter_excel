# exporter_excel
Модуль установит необходимый для его работы инофблок.
Привязка инфоблоков производится в выбранные сайты во время установки модуля.

Взаимодействие с функционалом модуля производится по пути: Сервисы - Excel импорт - Страница модуля

Пример xls файла:
```text
**********************************************************************************************************************************************************************
* По дням	Контрагент	Артикул 	Номенклатура	Характеристика номенклатуры	Документ движения (регистратор)	Количество	Сумма продажи в руб. *
**********************************************************************************************************************************************************************
```
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
```

Файл сохраняется по пути
```
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $module_id . '/';
```