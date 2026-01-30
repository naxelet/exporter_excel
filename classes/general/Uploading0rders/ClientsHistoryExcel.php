<?php

namespace Uploading0rders;

use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use \PhpOffice\PhpSpreadsheet\Reader;
use \PhpOffice\PhpSpreadsheet\IOFactory;
use \PhpOffice\PhpSpreadsheet\Reader\IReader;
use \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use \PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use \Uploading0rders\Interfaces\ImportExelInterface;
use \Uploading0rders\Mapper\ColumnExcelMapper;

//use \PhpOffice\PhpSpreadsheet\Spreadsheet;

class ClientsHistoryExcel implements ImportExelInterface
{
    protected ?IReader $reader = null;
    protected ?string $inputFileType = null;
    protected ?Spreadsheet $spreadsheet = null;
    protected ?Worksheet $activeWorksheet = null;

    /**
     * @param string $inputFilePath Путь к файлу
     * @param int $activeSheetIndex Индекс листа (Начинается с 0)
     * @param array $columnMapping Маппинг колонок [['index' => 0, 'code' => 'NAME', 'type' => 'string']]
     */
    public function __construct(
        private readonly string                     $inputFilePath,
        private int                                 $activeSheetIndex = 0,
        private readonly ?ColumnExcelMapper $columnMapping = null
    )
    {
        if (!file_exists($this->inputFilePath)) {
            throw new \PhpOffice\PhpSpreadsheet\Reader\Exception('Файл не найден: ' . $this->inputFilePath);
            //throw new \PhpOffice\PhpSpreadsheet\Reader\Exception(Loc::getMessage('XML_READER_FILE_NOT_FOUND', ['#FILE#' => $this->inputFilePath]));
        }
        $this->prepare();
        // $requiredColumns = ['NAME', 'ARTICLE', 'PRICE'];
        // if (!$reader->validateStructure($requiredColumns)) {
        //     throw new RuntimeException('Неверная структура файла');
        // }
    }

    /**
     * Подготовить класс к работе с файлом
     * @return void
     */
    protected function prepare(): void
    {
        $this->loadFile();
        $this->setActiveSheet();
        $this->activeWorksheet->removeColumnByIndex(1); // Первый столбец пустой
        //var_dump($this->activeWorksheet);
    }

    /**
     * Инициировать загрузку файла
     * @return void
     */
    protected function loadFile(): void
    {
        $this->inputFileType = IOFactory::identify($this->inputFilePath);
        $this->reader = IOFactory::createReader($this->inputFileType);
        $this->spreadsheet = $this->reader->load($this->inputFilePath);
    }

    /**
     * @param int|null $sheet_index Индекс листа (Начинается с 0)
     * @return void
     */
    public function setActiveSheet(?int $sheet_index = null): void
    {
        if (!empty($sheet_index)) {
            $this->activeSheetIndex = $sheet_index;
        }
        $this->activeWorksheet = $this->spreadsheet->setActiveSheetIndex($this->activeSheetIndex);
    }

    /**
     * @param int $start_row Начинать со строки
     * @return Generator
     * @throws \Exception
     */
    public function getRows(int $start_row = 1): \Generator
    {
        $max_row = $this->activeWorksheet->getHighestDataRow();
        $max_column = $this->activeWorksheet->getHighestDataColumn();
        $highest_column_index = Coordinate::columnIndexFromString($max_column);

        if ($max_row < 1) {
            throw new \Exception('Файл не содержит данных для импорта');
        }
        for ($row = $start_row; $row <= ($max_row - 2); $row++) {
            yield $this->readRow($row, $highest_column_index);
        }
    }

    /**
     * @param int $index_row номер строки
     * @param int $highest_column_index размер столбцов
     * @return array
     * @throws \Exception
     */
    private function readRow(int $index_row, int $highest_column_index): array
    {
        $row = [];
        // Используем маппинг
        if (!empty($this->columnMapping)) {
            foreach ($this->columnMapping->getMappingSchema() as $key_mapping => $mapping) {
                $this->columnMapping->validate($mapping, $key_mapping);
                $index_col = $mapping['index']; // Индекс начинается с 0
                if ($index_col <= $highest_column_index) {
                    $value_col = $this->activeWorksheet->getCell([$index_col, $index_row])->getValue();
                    $row[$mapping['code']] = ColumnExcelMapper::normalizeValue($value_col, $mapping['type'] ?? 'string');
                }
            }
            return $row;
        }

        // Используем числовые индексы
        for ($index_col = 1; $index_col <= $highest_column_index; $index_col++) {
            $value = $this->activeWorksheet->getCell([$index_col, $index_row])->getValue();
            $row[$index_col - 1] = $value;
        }

        return $row;
    }

    /**
     * Получить статистику файла
     */
    public function getFileStatistics(): array
    {

        return [
            'rows' => (int)$this->activeWorksheet->getHighestDataRow(),
            'columns' => Coordinate::columnIndexFromString($this->activeWorksheet->getHighestDataColumn()),
            'sheet_name' => $this->activeWorksheet->getTitle(),
            'file_size' => filesize($this->inputFilePath),
        ];
    }

    /**
     * Проверка структуры файла
     */
    public function validateStructure(array $requiredColumns): bool
    {
        // Проверяем по маппингу
        $mappedCodes = array_column($this->columnMapping->getMappingSchema(), 'code');
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $mappedCodes, true)) {
                return false;
            }
        }

        return true;
    }

    public function __destruct()
    {
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet, $this->activeWorksheet);
    }
}