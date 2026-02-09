<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

CModule::IncludeModule('iblock');

$paramIBlockTypes = [];
$paramIBlocks = [];
// Получение списка типов инфоблоков
$dbIBlockTypes = \CIBlockType::GetList(array('SORT' => 'ASC'), array('ACTIVE' => 'Y'));
while ($arIBlockTypes = $dbIBlockTypes->GetNext()) {
    $paramIBlockTypes[$arIBlockTypes['ID']] = $arIBlockTypes['ID'];
}

// Получение списка инфоблоков заданного типа
$dbIBlocks = \CIBlock::GetList(
    array(
        'SORT'  =>  'ASC'
    ),
    array(
        'ACTIVE'    =>  'Y',
        'TYPE'      =>  $arCurrentValues['IBLOCK_TYPE'],
    )
);
while ($arIBlocks = $dbIBlocks->GetNext()) {
    $paramIBlocks[$arIBlocks['ID']] = '[' . $arIBlocks['ID'] . '] ' . $arIBlocks['NAME'];
}


$arComponentParameters = array(
    'GROUPS' => array(),
    'PARAMETERS' => array(
        'IBLOCK_TYPE' => array(
            'PARENT' => 'BASE',
            'NAME' => GetMessage('AK_PARAMETERS_IBLOCK_TYPE_NAME'),
            'TYPE' => 'LIST',
            'ADDITIONAL_VALUES' => 'Y',
            'VALUES' => $paramIBlockTypes,
            'REFRESH' => 'Y',
            'MULTIPLE'  =>  'N',
        ),
        'IBLOCK_ID' =>  array(
            'PARENT'    =>  'BASE',
            'NAME'      =>  GetMessage('AK_PARAMETERS_IBLOCK_ID_NAME'),
            'TYPE'      =>  'LIST',
            'VALUES'    =>  $paramIBlocks,
            'REFRESH'   =>  'Y',
            'MULTIPLE'  =>  'N',
        ),
        'CACHE_TIME' =>  array('DEFAULT'=>3600),
    ),
);
