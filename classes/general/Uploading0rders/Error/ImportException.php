<?php

namespace Uploading0rders\Error;

/**
 * Исключение импорта
 */
class ImportException extends \RuntimeException
{
    private array $context;
    private int $rowNumber;

    public function __construct(
        string $message = "",
        array $context = [],
        int $rowNumber = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->context = $context;
        $this->rowNumber = $rowNumber;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getRowNumber(): int
    {
        return $this->rowNumber;
    }
}