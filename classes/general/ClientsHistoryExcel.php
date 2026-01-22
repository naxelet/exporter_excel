<?php
namespace Akatan\ExcelImporter;


class ClientsHistoryExcel
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
    public function __construct (
        private readonly string $inputFilePath,
        private int             $activeSheetIndex = 0,
        private array           $columnMapping = []
    ) {
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
     */
    public function getRows(int $start_row = 1): \Generator
    {
        $max_row = $this->activeWorksheet->getHighestDataRow();
        $max_column = $this->activeWorksheet->getHighestDataColumn();
        $highest_column_index = Coordinate::columnIndexFromString($max_column);
        for ($row = $start_row; $row <= $max_row; $row++) {
            yield $this->readRow($row, $highest_column_index);
        }
    }

    /**
     * @param int $index_row номер строки
     * @param int $highest_column_index размер столбцов
     * @return array
     */
    private function readRow(int $index_row, int $highest_column_index): array
    {
        $row = [];
        // Используем маппинг
        if (!empty($this->columnMapping)) {
            foreach ($this->columnMapping as $mapping) {
                $index_col = $mapping['index'] + 1; // Индекс начинается с 0
                if ($index_col <= $highest_column_index) {
                    $value_col = $this->activeWorksheet->getCell([$index_col, $index_row])->getValue();
                    $row[$mapping['code']] = $this->normalizeValue($value_col, $mapping['type'] ?? 'string');
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
     * Нормализация значений в ячейках при мапинге
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private function normalizeValue(mixed $value, string $type = 'string'): mixed
    {
        if (empty($value)) {
            return null;
        }
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'date':
                if ($value instanceof \DateTime) {
                    return $value->format('d.m.Y');
                }
                return (string)$value;
            case 'datetime':
                if ($value instanceof \DateTime) {
                    return $value->format('d.m.Y H:i:s');
                }
                return (string)$value;
            default:
                return (string)$value;
        }
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

    public function __destruct()
    {
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet, $this->activeWorksheet);
    }
}