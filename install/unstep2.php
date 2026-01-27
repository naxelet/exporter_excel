<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $APPLICATION;

if (!check_bitrix_sessid()) {
    return;
}

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$saveData = $request->getPost('savedata') === 'Y';
?>

<div style="max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="font-size: 24px; color: #28a745; margin-bottom: 10px;">
            ✓ <?= Loc::getMessage('AKATAN_EXCEL_UNINSTALL_COMPLETE') ?>
        </div>
        <div style="font-size: 18px; color: #333; margin-bottom: 20px;">
            <?= Loc::getMessage('AKATAN_EXCEL_UNINSTALL_SUCCESS') ?>
        </div>
    </div>

    <?php

    if ($saveData) {
        ?>
        <div style="background: #d4edda; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #28a745;">
            <div style="color: #155724;">
                💾 <?= Loc::getMessage('AKATAN_EXCEL_DATA_SAVED') ?>
            </div>
            <div style="margin-top: 10px;">
                <?= Loc::getMessage('AKATAN_EXCEL_DATA_SAVED_DESC') ?>
            </div>
        </div>
        <?php
    } else {
        ?>
        <div style="background: #f8d7da; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
            <div style="color: #721c24;">
                🗑 <?= Loc::getMessage('AKATAN_EXCEL_DATA_DELETED') ?>
            </div>
            <div style="margin-top: 10px;">
                <?= Loc::getMessage('AKATAN_EXCEL_DATA_DELETED_DESC') ?>
            </div>
        </div>
        <?php
    }
    ?>

    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #dee2e6;">
        <a href="/bitrix/admin/partner_modules.php?lang=<?= LANGUAGE_ID ?>"
           style="display: inline-block; padding: 10px 20px; background: #0069b4; color: white; text-decoration: none; border-radius: 4px;">
            ← <?= Loc::getMessage('AKATAN_EXCEL_BACK_TO_LIST') ?>
        </a>
    </div>
</div>