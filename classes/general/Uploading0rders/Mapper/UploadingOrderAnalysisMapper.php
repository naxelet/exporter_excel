<?php

namespace Uploading0rders\Mapper;

use Uploading0rders\Mapper\AbstractDataMapper;

class UploadingOrderAnalysisMapper extends AbstractDataMapper
{
    protected function getDefaultConfig(): array
    {
        return [];
    }

    protected function buildMappingSchema(): array
    {
        return [
            'element' => [
                'NAME' => ['source' => 'NAME', 'type' => 'string'],
                'CODE' => ['source' => 'CODE', 'type' => 'string'],
            ],
            'properties' => [
                'BY_DATE'=> ['source' => 'BY_DATE', 'type' => 'date'],
//                'UF_USER_1C'=> ['source' => 'BIND_USER_1C', 'type' => 'string'],
                'BIND_USER_1C_STRING'=> ['source' => 'BIND_USER_1C_STRING', 'type' => 'string'],
                'COUNTERPARTY' => ['source' => 'COUNTERPARTY', 'type' => 'string'],
                'ARTICLE'=> ['source' => 'ARTICLE', 'type' => 'string'],
                'NOMENCLATURE' => ['source' => 'NOMENCLATURE', 'type' => 'string'],
                'CHAR_NOMENCLATURE'=> ['source' => 'CHAR_NOMENCLATURE', 'type' => 'string'],
                'MOTION_DOCUMENT' => ['source' => 'MOTION_DOCUMENT', 'type' => 'string'],
                'QUANTITY' => ['source' => 'QUANTITY', 'type' => 'int'],
                'AMOUNT' => ['source' => 'AMOUNT', 'type' => 'float'],
            ],
        ];
    }

    /**
     * @array $row
     * @int $rowIndex
     * @throws \Exception
     */
    public function validate(array $row, int $rowIndex): void
    {
        $this->validateRequired($row, ['COUNTERPARTY', 'MOTION_DOCUMENT']);
    }

    /**
     * @array $row
     * @int $rowIndex
     * @throws \Exception
     */
    public function map(array $row, int $rowIndex): array
    {
        $this->validate($row, $rowIndex);
        $fields = [];
        if (!isset($row['NAME'])) {
            $fields['NAME'] = static::normalizeValue($row['COUNTERPARTY']) .
                '_' . static::normalizeValue($row['MOTION_DOCUMENT']) .
                '_' . static::normalizeValue($row['CHAR_NOMENCLATURE']);
        }

        foreach ($this->mappingSchema['properties'] as $key => $property) {
            if (!empty($row[$key])) {
                $fields['PROPERTY_VALUES'][$property['source']] = static::normalizeValue($row[$key], $property['type']);
            }
        }
        return $fields;
    }
}