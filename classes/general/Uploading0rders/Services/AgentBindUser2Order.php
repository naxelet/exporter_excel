<?php

namespace Uploading0rders\Services;

use \Bitrix\Iblock\Elements\Elementuploading0rderTable;
use \Bitrix\Iblock\IblockTable;
use \Bitrix\Main\Diag\FileLogger;
use \Bitrix\Main\UserTable;

class AgentBindUser2Order
{
    const MODULE_ID = 'akatan.exporterexcel';
    const API_CODE = 'uploading0rder';

    public static function runBindUser2Order(): string
    {
        $log_module_dir = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . static::MODULE_ID . '/logs/';
        $log_path = $log_module_dir . 'import_' . date('Y-m-d') . '.log';
        $logger = new FileLogger($log_path);
        $logger->setLevel(\Psr\Log\LogLevel::DEBUG);
        try {
            $iblock = IblockTable::query()
                ->where('API_CODE', 'uploading0rder')
                ->setLimit(1)
                ->fetchObject();
            $iblock_id = $iblock->get('id');
            $orders = static::getOrderEmptyBindUser($iblock_id);
            foreach ($orders as $key_order => $item_order) {
                $user_id = static::findUserByExternalCode($item_order['BIND_USER_1C_STRING_VALUE']);

                if (!empty($user_id)) {
                    $props = [
                        'BIND_USER_1C' => $user_id
                    ];
                    static::setProperty2Order((int) $item_order['ID'],(int) $iblock_id, $props);
                }
                unset($user_id);
            }
        } catch (\Exception $exception) {
            $logger->error("Ошибка при приклеплении пользователя: {error}<br>context: {context}", [
                'error' => $exception->getMessage(),
                'context' => $exception->getContext(),
            ]);
        } finally {
            return '\Uploading0rders\Services\AgentBindUser2Order::runBindUser2Order();';
        }
    }

    protected static function setProperty2Order(int|string $element_id, int|string $iblock_id, array $props): bool
    {
        if ($set_props_el = \CIBlockElement::SetPropertyValuesEx($element_id, $iblock_id, $props)) {
            return true;
        } else {
            $set_props_el->LAST_ERROR;
            return false;
        }

    }

    protected static function getOrderEmptyBindUser($iblock_id): array
    {
        return Elementuploading0rderTable::getlist([
            'select' => [
                'ID',
                'NAME',
                'CODE',
                'BY_DATE_' => 'BY_DATE',
                'COUNTERPARTY_' => 'COUNTERPARTY',
                'ARTICLE_' => 'ARTICLE',
                'NOMENCLATURE_' => 'NOMENCLATURE',
                'CHAR_NOMENCLATURE_' => 'CHAR_NOMENCLATURE',
                'MOTION_DOCUMENT_' => 'MOTION_DOCUMENT',
                'QUANTITY_' => 'QUANTITY',
                'AMOUNT_' => 'AMOUNT',
                'BIND_USER_1C_STRING_' => 'BIND_USER_1C_STRING',
                'BIND_USER_1C_' => 'BIND_USER_1C',
            ],
            'filter' => [
                'IBLOCK_ID' => $iblock_id,
                '=BIND_USER_1C_VALUE' => '',
                '!=BIND_USER_1C_STRING_VALUE' => ''
            ],
            'count_total' => true,
        ])->fetchAll();
    }

    /**
     * Поиск пользователя по коду поля
     */
    public static function findUserByExternalCode(string $externalCode): ?int
    {

        try {
            $user = UserTable::getRow([
                'select' => ['ID', 'UF_USER_1C'],
                'filter' => [
                    '=UF_USER_1C' => $externalCode,
                    'ACTIVE' => 'Y',
                ],
                'cache' => ['ttl' => 300]
            ]);

            return $user ? (int)$user['ID'] : null;

        } catch (\Throwable $error) {
            // ("Ошибка поиска пользователя по внешнему коду {$externalCode}: " . $error->getMessage(), 'ERROR');
            return null;
        }
    }
}