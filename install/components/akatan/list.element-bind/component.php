<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Iblock\Elements\Elementuploading0rderTable;

function getElements(int $iblock_id): array
{
    global $USER;

    $items = [];

    $elements = Elementuploading0rderTable::getList([
        'select' => [
            'ID', 'NAME', 'CODE',
            'BY_DATE_' => 'BY_DATE',
            'COUNTERPARTY_' => 'COUNTERPARTY',
            'ARTICLE_' => 'ARTICLE',
            'NOMENCLATURE_' => 'NOMENCLATURE',
            'CHAR_NOMENCLATURE_' => 'CHAR_NOMENCLATURE',
            'MOTION_DOCUMENT_' => 'MOTION_DOCUMENT',
            'QUANTITY_' => 'QUANTITY',
            'AMOUNT_' => 'AMOUNT',
            'BIND_USER_1C_' => 'BIND_USER_1C',
        ],
        'filter' => [
            'IBLOCK_ID' => $iblock_id,
            '=BIND_USER_1C_VALUE' => $USER->GetID(),
        ],
    ])->fetchAll();
    foreach ($elements as $element) {
        $element['BIND_USER_1C'] = getBindUser((int) $element['BIND_USER_1C_VALUE']);
        $items[] = $element;
    }
    return $items;
}

function getBindUser(?int $user_id): array
{
    $user = [];
    if (!empty($user_id)) {
        $rsUser = \CUser::GetByID($user_id);
        $arUser = $rsUser->Fetch();
        $user['WORK_COMPANY'] = $arUser['WORK_COMPANY'];
        $user['PERSONAL_NOTES'] = $arUser['PERSONAL_NOTES'];
        $user['WORK_NOTES'] = $arUser['WORK_NOTES'];
    }
    return $user;
}

if ($this->StartResultCache()) {

    CModule::IncludeModule('iblock');

    $arResult['ITEMS'] = getElements((int) $arParams['IBLOCK_ID']);

    $this->IncludeComponentTemplate();
}