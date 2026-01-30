<?php
const ADMIN_MODULE_NAME = 'akatan.exporterexcel';

global $APPLICATION;

$moduleAccessLevel = $APPLICATION->GetGroupRight(ADMIN_MODULE_NAME);

if ($moduleAccessLevel > 'D')
{
}