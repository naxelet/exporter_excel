<?php

namespace Uploading0rders\Mapper;

use \Uploading0rders\Error\ImportException;
use \Uploading0rders\Interfaces\DataMapperInterface;

/**
 * Базовый маппер
 */
abstract class AbstractDataMapper implements DataMapperInterface
{
    protected array $config;
    protected array $mappingSchema = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->mappingSchema = $this->buildMappingSchema();
    }

    abstract protected function getDefaultConfig(): array;

    abstract protected function buildMappingSchema(): array;

    public function getMappingSchema(): array
    {
        return $this->mappingSchema;
    }

    /**
     * Преобразует значение по типу
     */
    public static function normalizeValue(mixed $value, string $type = 'string'): mixed
    {
        if (empty($value)) {
            return null;
        }

        return match (strtolower($type)) {
            'int', 'integer' => (int)$value,
            'float', 'double', 'decimal' => (float)$value,
            'bool', 'boolean' => (bool)$value,
            'date' => static::normalizeDate($value, 'Y-m-d'),
            'datetime' => static::normalizeDate($value, 'Y-m-d H:i:s'),
            'timestamp' => static::normalizeDate($value, 'U'),
            'array' => is_array($value) ? $value : explode(',', (string)$value),
            default => (string)$value,
        };
    }

    /**
     * Нормализация дат
     */
    protected static function normalizeDate(mixed $value, string $format): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        if (is_numeric($value)) {
            $date = new \DateTime();
            $date->setTimestamp((int)$value);
            return $date->format($format);
        }

        $value = trim((string)$value);
        if (empty($value)) {
            return null;
        }

        try {
            $date = new \DateTime($value);
            return $date->format($format);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Проверяет обязательные поля
     */
    protected function validateRequired(array $row, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($row[$field]) || (is_string($row[$field]) && trim($row[$field]) === '')) {
                throw new ImportException(
                    "Обязательное поле '{$field}' отсутствует или пустое",
                    ['field' => $field, 'row' => $row]
                );
            }
        }
    }
}