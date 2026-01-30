<?php

namespace Uploading0rders\Interfaces;

use \Uploading0rders\Interfaces\DataMapperInterface;
use \Uploading0rders\Services\ImportResult;

interface BatchProcessorInterface
{
    public function setConfig(array $config): void;
    public function import(iterable $dataGenerator): ImportResult;//array; //
    public function getConfig(): array;
    public function getResult(): ImportResult;//array; //
    public function reset(): void;
}