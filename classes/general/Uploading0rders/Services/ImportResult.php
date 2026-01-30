<?php

namespace Uploading0rders\Services;

class ImportResult
{
    public float $startTime = 0;
    public float $endTime = 0;
    public float $executionTime = 0;
    public int $memoryStart = 0;
    public int $memoryPeak = 0;

    public int $totalProcessed = 0;
    public int $successRows = 0;
    public int $totalRows = 0;
    public int $created = 0;
    public int $updated = 0;
    public int $failed = 0;
    public int $validated = 0;
    public int $skipped = 0;

    public array $successIds = [];
    public array $errors = [];
    public array $warnings = [];

    /**
     * Добавление ошибки
     */
    public function addError(int $row, \Throwable $exception, array $context = []): void
    {
        $this->errors[] = [
            'row' => $row,
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
            'code' => $exception->getCode(),
            'context' => $context,
            'timestamp' => (new \DateTime)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Добавление предупреждения
     */
    public function addWarning(int $row, string $message, array $context = []): void
    {
        $this->warnings[] = [
            'row' => $row,
            'message' => $message,
            'context' => $context,
            'timestamp' => (new \DateTime)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Проверка успешности операции
     */
    public function isSuccess(): bool
    {
        return $this->failed === 0 && $this->totalProcessed > 0;
    }

    /**
     * Получение общего количества успешных операций
     */
    public function getSuccessCount(): int
    {
        return $this->created + $this->updated;
    }

    /**
     * Получение процента успешных операций
     */
    public function getSuccessRate(): float
    {
        if ($this->totalProcessed === 0) {
            return 0.0;
        }

        return ($this->successRows / $this->totalProcessed) * 100;
    }

    /**
     * Преобразование в массив
     */
    public function toArray(): array
    {
        return [
            'execution_time' => $this->executionTime,
            'total_processed' => $this->totalProcessed,
            'success_rows' => $this->successRows,
            'created' => $this->created,
            'updated' => $this->updated,
            'failed' => $this->failed,
            'validated' => $this->validated,
            'skipped' => $this->skipped,
            'success_rate' => $this->getSuccessRate(),
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
            'success_ids_count' => count($this->successIds),
        ];
    }

    /**
     * Получение статистики в виде строки
     */
    public function getStatsString(): string
    {
        return sprintf(
            "Всего: %d, Успешно: %d (Создано: %d, Обновлено: %d), Ошибок: %d, Время: %.2f сек",
            $this->totalProcessed,
            $this->successRows,
            $this->created,
            $this->updated,
            $this->failed,
            $this->executionTime
        );
    }
}