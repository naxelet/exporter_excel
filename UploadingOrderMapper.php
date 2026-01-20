<?php
class UploadingOrderMapper
{
    public function __construct()
    {
    }

    public function createOrderPropertyTemplate(): callable
    {
        return function ($row)
        {
            return [
                'element' => [
                    'NAME' => $row['NAME'] ?? '',
                    'XML_ID' => $row['ARTICLE'] ?? '',
                    'CODE' => $this->generateElementCode($row['NAME'] ?? ''),
                    'PREVIEW_TEXT' => $row['DESCRIPTION'] ?? '',
                    'ACTIVE' => ($row['ACTIVE'] ?? 'Y') === 'Y' ? 'Y' : 'N',
                    'SORT' => (int)($row['SORT'] ?? 500),
                ],
                'properties' => [
                    'PRICE' => (float)($row['PRICE'] ?? 0),
                    'OLD_PRICE' => (float)($row['OLD_PRICE'] ?? 0),
                    'QUANTITY' => (int)($row['QUANTITY'] ?? 0),
                    'BRAND' => $row['BRAND'] ?? '',
                    'COLOR' => $row['COLOR'] ?? '',
                    'SIZE' => $row['SIZE'] ?? '',
                    'MATERIAL' => $row['MATERIAL'] ?? '',
                    'WEIGHT' => (float)($row['WEIGHT'] ?? 0),
                    'LENGTH' => (float)($row['LENGTH'] ?? 0),
                    'WIDTH' => (float)($row['WIDTH'] ?? 0),
                    'HEIGHT' => (float)($row['HEIGHT'] ?? 0),
                ],
                'mode' => 'create'
            ];
        };
    }
}