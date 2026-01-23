<?php

namespace Uploading0rders\Interfaces;

/**
 * Интерфейс для мапперов данных
 */
interface DataMapperInterface
{
    /**
     * Преобразует значение по типу
     * @param mixed $value Значение для преобразования
     * @param string $type Тип значения, в который производится нормализация
     * @return mixed
     */
    public static function normalizeValue(mixed $value, string $type = 'string'): mixed;

    /**
     * Преобразует сырые данные в структурированные для импорта
     */
    public function map(array $row, int $rowIndex): array;

    /**
     * Валидирует входные данные перед маппингом
     */
    public function validate(array $row, int $rowIndex): void;

    /**
     * Возвращает схему маппинга
     */
    public function getMappingSchema(): array;
}