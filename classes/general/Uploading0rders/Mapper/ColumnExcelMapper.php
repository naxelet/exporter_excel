<?php

namespace Uploading0rders\Mapper;

use Uploading0rders\Mapper\AbstractDataMapper;

class ColumnExcelMapper extends AbstractDataMapper
{

    protected function getDefaultConfig(): array
    {
        return [];
    }

    protected function buildMappingSchema(): array
    {
        return [
            ['index' => 1, 'code' => 'BY_DATE', 'type' => 'date'],
            ['index' => 2, 'code' => 'UF_USER_1C', 'type' => 'string'],
            ['index' => 3, 'code' => 'COUNTERPARTY', 'type' => 'string'],
            ['index' => 4, 'code' => 'ARTICLE', 'type' => 'string'],
            ['index' => 5, 'code' => 'NOMENCLATURE', 'type' => 'string'],
            ['index' => 6, 'code' => 'CHAR_NOMENCLATURE', 'type' => 'string'],
            ['index' => 7, 'code' => 'MOTION_DOCUMENT', 'type' => 'string'],
            ['index' => 8, 'code' => 'QUANTITY', 'type' => 'int'],
            ['index' => 9, 'code' => 'AMOUNT', 'type' => 'float'],
        ];
    }

    /**
     * @array $row
     * @int $rowIndex
     * @throws \Exception
     */
    public function validate(array $row, int $rowIndex): void
    {
        $this->validateRequired($row, ['index', 'code']);

        if (isset($row['index']) && (int) $row['index'] < 0) {
            throw new \Exception(
                "Index должен быть положительным",
                ['field' => 'index', 'value' => $row['index'], 'row_index' => $rowIndex]
            );
        }

        if (isset($row['code']) && mb_strlen($row['code']) === 0) {
            throw new \Exception(
                "code необходимо заполнить",
                ['field' => 'code', 'value' => $row['code'], 'row_index' => $rowIndex]
            );
        }
    }

    /**
     * @array $row
     * @int $rowIndex
     * @throws \Exception
     */
    public function map(array $row, int $rowIndex): array
    {
        throw new \Exception('Данный метод для выгрузки из Excel отсутствует.');
    }
}