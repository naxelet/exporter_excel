<?php

namespace Uploading0rders\Processor;

use Bitrix\Main\DB\SqlQueryException;
use \Uploading0rders\ImportIblockService;
use \Uploading0rders\Interfaces\BatchProcessorInterface;
use \Uploading0rders\Interfaces\DataMapperInterface;


/**
 * Пакетный процессор для импорта данных
 */
abstract class BatchProcessor implements BatchProcessorInterface
{
    const VALID_MODES = ['create', 'update', 'create_or_update'];
    protected array $config;
    private array $statistics = [];
    private array $batchBuffer = [];
    //private ImportResult $currentResult;

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
        //$this->currentResult = new ImportResult();
        $this->initializeStatistics();
        $this->validateSetting($this->config);
    }

    abstract protected function getDefaultConfig(): array;

    abstract protected function processItem(array $item, int $index): void;

    private function initializeStatistics(): void
    {
        $this->statistics = [
            'started_at' => null,
            'finished_at' => null,
            'total_rows' => 0,
            'processed_rows' => 0,
            'success_rows' => 0,
            'error_rows' => 0,
            'validation_errors' => 0,
            'processing_errors' => 0,
            'batches_processed' => 0,
            'execution_time' => 0,
            'memory_peak' => 0,
        ];
    }

    /**
     * @throws \Exception
     */
    private function validateSetting(array $setting): void
    {
        if (!in_array($setting['mode'] ?? 'create_or_update', static::VALID_MODES, true)) {
            throw new \Exception('Некорректный режим импорта');
            //throw new ImportException(Loc::getMessage('VALIDATION_INVALID_MODE'));
        }

        if ((int)$this->config['batch_size'] <= 0) {
            throw new \Exception('batch_size - натуральное число');
        }
    }

    public function setConfig(): array
    {
        return [];
    }

    public function import(iterable $dataGenerator): array //$result = new ImportResult();
    {
        $this->statistics['started_at'] = microtime(true);
        $memoryStart = memory_get_usage(true);
        try {
            foreach ($dataGenerator as $index => $row) {
                $this->processRow($row, $index, $this->mapper);

                // Обработка пачки при достижении лимита
                if (count($this->batchBuffer) >= $this->config['batch_size']) {
                    $this->processBatch();
                }

                // Лимит ошибок
//                if ($this->currentResult->failed >= $this->config['max_errors']) {
//                    $this->logger->warning('Достигнут лимит ошибок, остановка импорта');
//                    break;
//                }
//
//                // Callback прогресса
//                if ($this->config['progress_callback']) {
//                    call_user_func($this->config['progress_callback'], $index, $this->currentResult);
//                }
            }

            // Вызов callback прогресса
            if ($this->config['progress_callback']) {
                //call_user_func($this->config['progress_callback'], $index, $result);
            }

            $this->statistics['total_rows']++;

            // Обработка оставшейся пачки
            if (!empty($this->batchBuffer)) {
                $this->processBatch();
            }

        } catch (\Throwable $error) {
            echo $error->getMessage();
//            $this->handleCriticalError($error);
        }
        $this->finalizeStatistics($memoryStart);
        return [];
    }

    /**
     * Обработка одной строки данных
     */
    private function processRow(array $row, int $index): void
    {
        try {

            // Маппинг данных
            $mappedProps = $this->mapper->map($row, $index);

//            $this->currentResult->totalProcessed++;

            // Только валидация
//            if ($this->config['validate_only']) {
//                $this->currentResult->validated++;
//                return;
//            }

            // Сухой запуск (тестирование)
//            if ($this->config['dry_run']) {
//                $this->logger->debug("Dry run: строка {$index} обработана", $mappedData);
//                $this->currentResult->validated++;
//                return;
//            }

            // Добавление в буфер пачки
            $this->batchBuffer[] = [
                'properties' => $mappedProps,
                'index' => $index,
            ];

//        } catch (ImportException $e) {
//            $this->handleRowError($index, $e);
        } catch (\Throwable $error) {
            echo 'Неожиданная ошибка: ' . $error->getMessage();
//            $this->handleRowError($index, new ImportException(
//                'Неожиданная ошибка: ' . $error->getMessage(),
//                ['error' => $error],
//                $index
//            ));
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
        $application = \Bitrix\Main\Application::getInstance();
        $connection = $application->getConnection();

//        $this->logger->debug("Обработка пачки из " . count($this->batchBuffer) . " элементов");
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
            throw $exception;
        } catch (\Throwable $error) {
            $connection->rollbackTransaction();
            throw $error;
        }
//            } catch (ImportException $e) {
//                $this->handleRowError($item['index'], $e);
//            }

        // Очистка буфера
        $this->batchBuffer = [];

        // Очистка кэша сервиса для экономии памяти
        if ($this->currentResult->totalProcessed % 1000 === 0) {
//            $this->importService->clearCache();
        }
    }

//    /**
//     * Обработка одного элемента
//     */

//    private function processItem(array $item, int $index): void
//    {
//        $elementData = $item['element'] ?? [];
//        $properties = $item['properties'] ?? [];
//        $sections = $item['sections'] ?? [];
//
//        if ($this->config['upsert_mode']) {
//            // Режим upsert (создание или обновление)
//            $result = $this->importService->upsertElement(
//                $elementData,
//                $properties,
//                $sections,
//                $this->config['xml_id_field'],
//                true
//            );
//
//            if ($result['created']) {
//                $this->currentResult->created++;
//            } else {
//                $this->currentResult->updated++;
//            }
//
//        } else {
//            // Режим только создания
//            $elementId = $this->importService->createElement(
//                $elementData,
//                $properties,
//                $sections,
//                $this->config['use_transaction']
//            );
//
//            $this->currentResult->created++;
//        }
//
//        $this->currentResult->successRows++;
//    }

    /**
     * Финализация статистики
     */
    private function finalizeStatistics(int $memoryStart): void
    {
        $this->statistics['finished_at'] = microtime(true);
        $this->statistics['execution_time'] =
            $this->statistics['finished_at'] - $this->statistics['started_at'];
        $this->statistics['memory_peak'] = memory_get_peak_usage(true) - $memoryStart;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getResult(): array
    {
        return [];
    }

    public function reset(): void
    {
    }
}