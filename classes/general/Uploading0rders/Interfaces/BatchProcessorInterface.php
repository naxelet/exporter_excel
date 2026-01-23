<?php

namespace Uploading0rders\Interfaces;

use \Uploading0rders\Interfaces\DataMapperInterface;

interface BatchProcessorInterface
{
    public function setConfig(): array;
    public function import(iterable $dataGenerator): array; //ImportResult;
    public function getConfig(): array;
    public function getResult(): array; //ImportResult;
    public function reset(): void;
}