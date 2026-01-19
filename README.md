# exporter_excel
Пример использования
```
php
$inputFileName =  realpath(__DIR__ . '/../upload/clients-history/test.xls');
$activeSheetIndex = 0;
$mapping = [
    ['index' => 0, 'code' => 'BY_DATE', 'type' => 'date'],
    ['index' => 1, 'code' => 'COUNTERPARTY', 'type' => 'string'],
    ['index' => 2, 'code' => 'ARTICLE', 'type' => 'string'],
    ['index' => 3, 'code' => 'NOMENCLATURE', 'type' => 'string'],
    ['index' => 4, 'code' => 'CHAR_NOMENCLATURE', 'type' => 'string'],
    ['index' => 5, 'code' => 'DOCUMENT', 'type' => 'string'],
    ['index' => 6, 'code' => 'QUANTITY', 'type' => 'int'],
    ['index' => 7, 'code' => 'AMOUNT', 'type' => 'float'],
];
$excel_file = new ClientsHistoryExcel($inputFileName, $activeSheetIndex, $mapping);

echo '<pre>' . print_r($excel_file->getFileStatistics(),true) . '</pre>';
foreach ($excel_file->getRows(7) as $index => $row) {
    echo '<pre>' . print_r($row, true) . '</pre><br>';
}
```