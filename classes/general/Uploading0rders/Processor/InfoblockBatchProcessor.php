<?php

namespace Uploading0rders\Processor;

use \Uploading0rders\Processor\BatchProcessor;

class InfoblockBatchProcessor extends BatchProcessor
{
    protected function getDefaultConfig(): array
    {
        return [
            'batch_size' => 100,
            'max_errors' => 100,
            'skip_errors' => false,
            'dry_run' => false,
            'mode' => 'create_or_update'
        ];
    }

    /**
     * Обработка одного элемента
     * @param array $item
     * @param int $index
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    protected function processItem(array $item, int $index): int
    {
        $existingId = null;
        if ($this->config['mode'] !== 'create') {
            $existingId = $this->importService->findElementIdByCode($item);
        }
        if ($existingId > 0 && $this->config['mode'] !== 'create') {
            $update_id = $this->importService->updateElement($item);
            $this->currentResult->updated++;
            return $update_id;
        }
        $created_id = $this->importService->createElement($item);
        $this->currentResult->created++;
        return $created_id;
        /*switch ($this->config['mode']) {
            case 'create': {
                $this->importService->createElement($item);
                break;
            }
            case 'update': {
                $this->importService->updateElement($item);
                break;
            }
            case 'create_or_update': {

                break;
            }
        }*/
        //            if ($result['created']) {
//                $this->currentResult->created++;
//            } else {
//                $this->currentResult->updated++;
//            }
//        echo ('<pre>' . print_r($item, true) . '</pre>');
    }
}