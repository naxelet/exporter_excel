<?php

use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

if (!check_bitrix_sessid()) {
    return;
}

echo '<pre>' . Option::get('akatan.exporterexcel', 'IBLOCK_ID') . '</pre>';
echo '<pre>' . Option::get('akatan.exporterexcel', 'SELECTED_SITES') . '</pre>';
?>

<form action="<?= $APPLICATION->GetCurPage() ?>" method="post">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="id" value="akatan.exporterexcel">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">

    <?php CAdminMessage::ShowMessage(Loc::getMessage('AKATAN_EXCEL_UNINSTALL_WARNING')) ?>

    <p>
        <input type="checkbox" name="savedata" id="savedata" value="Y" checked>
        <label for="savedata"><?= Loc::getMessage('AKATAN_EXCEL_UNINSTALL_SAVE_DATA') ?></label>
    </p>

    <input type="submit" name="inst" value="<?= Loc::getMessage('AKATAN_EXCEL_UNINSTALL_DELETE') ?>">
</form>