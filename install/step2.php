<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\SiteTable;

Loc::loadMessages(__FILE__);

global $APPLICATION;

if (!check_bitrix_sessid()) {
    return;
}
$selectedSites = Option::get('akatan.exporterexcel', 'SELECTED_SITES', '');
$siteNames = [];

if ($selectedSites) {
    $dbSites = SiteTable::getList([
        'filter' => ['LID' => $selectedSites],
        'order' => ['SORT' => 'ASC']
    ]);

    while ($site = $dbSites->fetch()) {
        $siteNames[] = $site['NAME'] . ' [' . $site['LID'] . ']';
    }
}

// была ли ошибка при установке
if ($exception = $APPLICATION->GetException()) {
    // Выводим ошибку
    echo \CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('MOD_INST_ERR'), // (MOD_INST_ERR - системная языковая переменная)
        'DETAILS' => $exception->GetString(),
        'HTML' => true,
    ]);
} else {
    // MOD_INST_OK - системная языковая переменная
    echo \CAdminMessage::ShowNote(Loc::getMessage('MOD_INST_OK'));
}
?>
<div style="max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="font-size: 24px; color: #0069b4; margin-bottom: 10px;">
            ✓ <?= Loc::getMessage('AKATAN_EXCEL_INSTALL_COMPLETE') ?>
        </div>
        <div style="font-size: 18px; color: #333; margin-bottom: 20px;">
            <?= Loc::getMessage('AKATAN_EXCEL_INSTALL_SUCCESS') ?>
        </div>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 30px;">
        <h3 style="color: #0069b4; margin-top: 0; margin-bottom: 15px;">
            <?= Loc::getMessage('AKATAN_EXCEL_CREATED_IBLOCK') ?>
        </h3>

        <?php
        // Получаем ID созданного инфоблока
        $iblockId = Option::get('akatan.exporterexcel', 'IBLOCK_ID');

        if ($iblockId && CModule::IncludeModule('iblock')) {
            $res = CIBlock::GetByID($iblockId);
            if ($arIBlock = $res->GetNext()) {
                ?>
                <div style="margin-bottom: 15px;">
                    <strong><?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_NAME') ?>:</strong>
                    <span style="color: #28a745;"><?= htmlspecialcharsbx($arIBlock['NAME']) ?></span>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong><?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_CODE') ?>:</strong>
                    <code style="background: #e9ecef; padding: 2px 6px; border-radius: 3px;">
                        <?= htmlspecialcharsbx($arIBlock['CODE']) ?>
                    </code>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong><?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_ID') ?>:</strong>
                    <span style="font-weight: bold; color: #dc3545;"><?= $iblockId ?></span>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong><?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_TYPE') ?>:</strong>
                    <?= htmlspecialcharsbx($arIBlock['IBLOCK_TYPE_ID']) ?>
                </div>

                <?php if (!empty($siteNames)): ?>
                    <div style="margin-bottom: 15px;">
                        <strong><?= Loc::getMessage('AKATAN_EXCEL_ASSIGNED_SITES') ?>:</strong>
                        <ul style="margin: 5px 0 0 20px; padding: 0;">
                            <?php foreach ($siteNames as $siteName): ?>
                                <li style="margin-bottom: 3px;"><?= htmlspecialcharsbx($siteName) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 20px;">
                    <strong><?= Loc::getMessage('AKATAN_EXCEL_CREATED_FIELDS') ?>:</strong>
                    <ul style="margin: 10px 0 0 20px; padding: 0;">
                        <li>BY_DATE (Дата)</li>
                        <li>COUNTERPARTY (Контрагент)</li>
                        <li>ARTICLE (Артикул)</li>
                        <li>NOMENCLATURE (Номенклатура)</li>
                        <li>CHAR_NOMENCLATURE (Характеристика номенклатуры)</li>
                        <li>MOTION_DOCUMENT (Документ движения)</li>
                        <li>QUANTITY (Количество)</li>
                        <li>AMOUNT (Сумма)</li>
                    </ul>
                </div>

                <div style="margin-top: 20px;">
                    <a href="/bitrix/admin/iblock_edit.php?ID=<?= $iblockId ?>&type=<?= htmlspecialcharsbx($arIBlock['IBLOCK_TYPE_ID']) ?>&lang=<?= LANGUAGE_ID ?>"
                       style="display: inline-block; padding: 8px 16px; background: #0069b4; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;">
                        ✎ <?= Loc::getMessage('AKATAN_EXCEL_EDIT_IBLOCK') ?>
                    </a>

                    <a href="/bitrix/admin/iblock_element_admin.php?IBLOCK_ID=<?= $iblockId ?>&type=<?= htmlspecialcharsbx($arIBlock['IBLOCK_TYPE_ID']) ?>&lang=<?= LANGUAGE_ID ?>"
                       style="display: inline-block; padding: 8px 16px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">
                        📋 <?= Loc::getMessage('AKATAN_EXCEL_MANAGE_ELEMENTS') ?>
                    </a>
                </div>
                <?php
            }
        } else {
            ?>
            <div style="color: #dc3545;">
                ⚠ <?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_NOT_FOUND') ?>
            </div>
            <?php
        }
        ?>
    </div>

    <div style="background: #e8f4fd; padding: 20px; border-radius: 6px; margin-bottom: 30px; border-left: 4px solid #0069b4;">
        <h3 style="color: #0069b4; margin-top: 0; margin-bottom: 15px;">
            📌 <?= Loc::getMessage('AKATAN_EXCEL_NEXT_STEPS') ?>
        </h3>

        <ul style="margin: 0; padding-left: 20px;">
            <li style="margin-bottom: 8px;">
                <strong><?= Loc::getMessage('AKATAN_EXCEL_STEP_1') ?>:</strong>
                <?= Loc::getMessage('AKATAN_EXCEL_STEP_1_DESC') ?>
            </li>
            <li style="margin-bottom: 8px;">
                <strong><?= Loc::getMessage('AKATAN_EXCEL_STEP_2') ?>:</strong>
                <?= Loc::getMessage('AKATAN_EXCEL_STEP_2_DESC') ?>
            </li>
            </li>
        </ul>
    </div>

    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #dee2e6;">
        <a href="/bitrix/admin/partner_modules.php?lang=<?= LANGUAGE_ID ?>"
           style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;">
            ← <?= Loc::getMessage('AKATAN_EXCEL_BACK_TO_LIST') ?>
        </a>

        <a href="/bitrix/admin/akatan.exporterexcel__general.php?lang=<?= LANGUAGE_ID ?>"
           style="display: inline-block; padding: 10px 20px; background: #0069b4; color: white; text-decoration: none; border-radius: 4px;">
            ⚙ <?= Loc::getMessage('AKATAN_EXCEL_GO_TO_SETTINGS') ?>
        </a>
    </div>
</div>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="submit" name="" value="<?= Loc::getMessage("MOD_BACK") ?>">
</form>
