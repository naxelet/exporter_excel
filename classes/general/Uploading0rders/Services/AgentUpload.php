<?php

namespace Uploading0rders\Services;

abstract class AgentUpload
{

    const MODULE_ID = 'akatan.exporterexcel';
    const ALLOWED_EXTENSIONS = ['xml', 'xlsx', 'xls', 'csv'];
    abstract public static function runImportFile(): string;
}