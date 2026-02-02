<?php

namespace Uploading0rders\Processor;

use \Bitrix\Main\DB\SqlQueryException;
use \Bitrix\Main\Application;
use \Uploading0rders\ImportIblockService;
use \Uploading0rders\Interfaces\BatchProcessorInterface;
use \Uploading0rders\Interfaces\DataMapperInterface;
use \Uploading0rders\Services\ImportResult;
use \Uploading0rders\Error\ImportException;


/**
 * Пакетный процессор для импорта данных
 */
abstract class BatchProcessor implements BatchProcessorInterface
{
    const VALID_MODES = ['create', 'update', 'create_or_update'];
    protected array $config;
    private array $batchBuffer = [];
    protected ImportResult $currentResult;

    /**
     * Конструктор процессора
     *
     * @param ImportIblockService $importService Сервис импорта
     * @param DataMapperInterface $mapper Маппер
     * @param array $config Конфигурация
     * @throws \Exception
     */
    public function __construct(
        protected ImportIblockService          $importService,
        protected readonly DataMapperInterface $mapper,
        array                                  $config
    )
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->currentResult = new ImportResult();
        $this->validateSetting($this->config);
    }

    abstract protected function getDefaultConfig(): array;

    abstract protected function processItem(array $item, int $index): int;

    /**
     * @throws \Exception
     */
    private function validateSetting(array $setting): void
    {
        if (!in_array($setting['mode'] ?? 'create_or_update', static::VALID_MODES, true)) {
//            throw new ImportException(Loc::getMessage('VALIDATION_INVALID_MODE'));
            throw new ImportException('Некорректный режим импорта');
        }

        if ((int)$this->config['batch_size'] <= 0) {
            throw new \Exception('batch_size - натуральное число');
        }
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @param iterable $dataGenerator Строка из документа
     * @return ImportResult Результат импорта
     * @throws \Throwable
     */
    public function import(iterable $dataGenerator): ImportResult // array //$result = new ImportResult();
    {
        $this->reset();
        $this->currentResult->startTime = microtime(true);
        $this->currentResult->memoryStart = memory_get_usage(true);
        try {
            foreach ($dataGenerator as $index => $row) {
                $this->processRow($row, $index);

                // Обработка пачки при достижении лимита
                if (count($this->batchBuffer) >= $this->config['batch_size']) {
                    $this->processBatch();
                }

                // Лимит ошибок
//                if ($this->currentResult->failed >= $this->config['max_errors']) {
//                    log->warning('Достигнут лимит ошибок, остановка импорта');
//                    break;
//                }
//
//                // Callback прогресса
                if ($this->config['progress_callback']) {
                    call_user_func($this->config['progress_callback'], $index, $this->currentResult);
                }
            }

            $this->currentResult->totalRows++;

            // Обработка оставшейся пачки
            if (!empty($this->batchBuffer)) {
                $this->processBatch();
            }

        } catch (\Throwable $error) {
            $this->handleCriticalError($error);
        }
        $this->finalizeImport();
        return $this->currentResult;
    }

    /**
     * Обработка одной строки данных
     */
    private function processRow(array $row, int $index): void
    {
        try {

            // Маппинг данных
            $mappedProps = $this->mapper->map($row, $index);

            $this->currentResult->totalProcessed++;

            // Только валидация
            if ($this->config['validate_only']) {
                $this->currentResult->validated++;
                return;
            }

            // Сухой запуск (тестирование)
            if ($this->config['dry_run']) {
//                log->debug("Dry run: строка {$index} обработана", $mappedData);
                $this->currentResult->validated++;
                return;
            }

            // Добавление в буфер пачки
            $this->batchBuffer[] = [
                'properties' => $mappedProps,
                'index' => $index,
            ];

        } catch (ImportException $exception) {
            $this->handleRowError($index, $exception);
        } catch (\Throwable $error) {
            $this->handleRowError($index, new ImportException(
                'Неожиданная ошибка: ' . $error->getMessage(),
                ['error' => $error],
                $index
            ));
        }
    }

    /**
     * Обработка пачки данных
     * @throws SqlQueryException
     * @throws \Throwable
     */
    private function processBatch(): void
    {
        if (empty($this->batchBuffer)) {
            return;
        }
        $application = Application::getInstance();
        $connection = $application->getConnection();

//        log->debug("Обработка пачки из " . count($this->batchBuffer) . " элементов");
        try {
            // Транзакция для целостности данных
            $connection->startTransaction();
            foreach ($this->batchBuffer as $item) {
                $this->processItem($item['properties'], $item['index']);
            }
            $connection->commitTransaction();
        } catch (\Bitrix\Main\DB\TransactionException $exception) {
            // ROLLBACK
            $connection->rollbackTransaction();
            $this->handleCriticalError($exception);
        } catch (\Throwable $error) {
            $connection->rollbackTransaction();
            $this->handleCriticalError($error);
        }


        // Очистка буфера
        $this->batchBuffer = [];

        // Очистка кэша сервиса для экономии памяти
        if ($this->currentResult->totalProcessed % 1000 === 0) {
//            $this->importService->clearCache();
        }
    }

    /**
     * Обработка критической ошибки
     * @throws \Throwable
     */
    private function handleCriticalError(\Throwable $error): void
    {
        $this->currentResult->failed++;

        /*log->critical("Критическая ошибка импорта: " . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString(),
        ]);*/

        throw $error;
    }

    /**
     * Обработка ошибки строки
     */
    private function handleRowError(int $index, ImportException $exception): void
    {
        $this->currentResult->addError($index, $exception);
        $this->currentResult->failed++;

        /*log->error("Ошибка в строке {$index}: " . $exception->getMessage(), [
            'context' => $exception->getContext(),
        ]);*/

        // Прерывание при ошибках если настроено
        if (!$this->config['skip_errors']) {
            throw $exception;
        }
    }

    /**
     * Завершение импорта
     */
    private function finalizeImport(): void
    {
        $this->currentResult->endTime = microtime(true);
        $this->currentResult->executionTime =
            $this->currentResult->endTime - $this->currentResult->startTime;
        $this->currentResult->memoryPeak =
            memory_get_peak_usage(true) - $this->currentResult->memoryStart;

        // log->info('Импорт завершен', $this->currentResult->toArray());

        // Вывод статистики
        if ($this->importService->debugMode) {
            $this->printStatistics();
        }
    }
    /**
     * Вывод статистики
     */
    private function printStatistics(): void
    {
        $stats = $this->currentResult;

        echo "\n" . str_repeat('=', 60) . "\n";
        echo "СТАТИСТИКА ИМПОРТА\n";
        echo str_repeat('=', 60) . "\n";
        echo sprintf("Инфоблок: %s (ID: %d)\n",
            $this->importService->getIblockCode(),
            $this->importService->getIblockId()
        );
        echo sprintf("Всего обработано: %d\n", $stats->totalProcessed);
        echo sprintf("Успешно: %d\n", $stats->successRows);
        echo sprintf("  - Создано: %d\n", $stats->created);
        echo sprintf("  - Обновлено: %d\n", $stats->updated);
        echo sprintf("Ошибок: %d\n", $stats->failed);
        echo sprintf("Время выполнения: %.2f сек\n", $stats->executionTime);
        echo str_repeat('=', 60) . "\n";
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getResult(): ImportResult
    {
        return $this->currentResult;
    }

    public function reset(): void
    {
        $this->batchBuffer = [];
        $this->currentResult = new ImportResult();
    }
}