<?php
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $APPLICATION;

// Проверка CSRF токена
if (!check_bitrix_sessid()) {
    return;
}
?>

<div style="max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="font-size: 24px; color: #0069b4; margin-bottom: 10px;">
            ✓ <?= Loc::getMessage('MY_MODULE_INSTALL_COMPLETE') ?>
        </div>
        <div style="font-size: 18px; color: #333; margin-bottom: 20px;">
            <?= Loc::getMessage('MY_MODULE_INSTALL_SUCCESS') ?>
        </div>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 30px;">
        <h3 style="color: #0069b4; margin-top: 0; margin-bottom: 15px;">
            <?= Loc::getMessage('MY_MODULE_CREATED_IBLOCK') ?>
        </h3>

        <?php
        // Получаем ID созданного инфоблока
        $iblockId = \Bitrix\Main\Config\Option::get('my.module', 'IBLOCK_ID');

        if ($iblockId && CModule::IncludeModule('iblock')) {
            $res = CIBlock::GetByID($iblockId);
            if ($arIBlock = $res->GetNext()) {
                ?>
                <div style="margin-bottom: 15px;">
                    <strong><?= Loc::getMessage('MY_MODULE_IBLOCK_NAME') ?>:</strong>
                    <span style="color: #28a745;"><?= htmlspecialcharsbx($arIBlock['NAME']) ?></span>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong><?= Loc::getMessage('MY_MODULE_IBLOCK_CODE') ?>:</strong>
                    <code style="background: #e9ecef; padding: 2px 6px; border-radius: 3px;">
                        <?= htmlspecialcharsbx($arIBlock['CODE']) ?>
                    </code>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong><?= Loc::getMessage('MY_MODULE_IBLOCK_ID') ?>:</strong>
                    <span style="font-weight: bold; color: #dc3545;"><?= $iblockId ?></span>
                </div>

                <div style="margin-bottom: 20px;">
                    <strong><?= Loc::getMessage('MY_MODULE_IBLOCK_TYPE') ?>:</strong>
                    <?= htmlspecialcharsbx($arIBlock['IBLOCK_TYPE_ID']) ?>
                </div>

                <div style="margin-bottom: 20px;">
                    <strong><?= Loc::getMessage('MY_MODULE_CREATED_FIELDS') ?>:</strong>
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
                        ✎ <?= Loc::getMessage('MY_MODULE_EDIT_IBLOCK') ?>
                    </a>

                    <a href="/bitrix/admin/iblock_element_admin.php?IBLOCK_ID=<?= $iblockId ?>&type=<?= htmlspecialcharsbx($arIBlock['IBLOCK_TYPE_ID']) ?>&lang=<?= LANGUAGE_ID ?>"
                       style="display: inline-block; padding: 8px 16px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">
                        📋 <?= Loc::getMessage('MY_MODULE_MANAGE_ELEMENTS') ?>
                    </a>
                </div>
                <?php
            }
        } else {
            ?>
            <div style="color: #dc3545;">
                ⚠ <?= Loc::getMessage('MY_MODULE_IBLOCK_NOT_FOUND') ?>
            </div>
            <?php
        }
        ?>
    </div>

    <div style="background: #e8f4fd; padding: 20px; border-radius: 6px; margin-bottom: 30px; border-left: 4px solid #0069b4;">
        <h3 style="color: #0069b4; margin-top: 0; margin-bottom: 15px;">
            📌 <?= Loc::getMessage('MY_MODULE_NEXT_STEPS') ?>
        </h3>

        <ul style="margin: 0; padding-left: 20px;">
            <li style="margin-bottom: 8px;">
                <strong><?= Loc::getMessage('MY_MODULE_STEP_1') ?>:</strong>
                <?= Loc::getMessage('MY_MODULE_STEP_1_DESC') ?>
            </li>
            <li style="margin-bottom: 8px;">
                <strong><?= Loc::getMessage('MY_MODULE_STEP_2') ?>:</strong>
                <?= Loc::getMessage('MY_MODULE_STEP_2_DESC') ?>
            </li>
            <li style="margin-bottom: 8px;">
                <strong><?= Loc::getMessage('MY_MODULE_STEP_3') ?>:</strong>
                <a href="/bitrix/admin/my_module_settings.php?lang=<?= LANGUAGE_ID ?>" style="color: #0069b4; text-decoration: underline;">
                    <?= Loc::getMessage('MY_MODULE_STEP_3_DESC') ?>
                </a>
            </li>
            <li>
                <strong><?= Loc::getMessage('MY_MODULE_STEP_4') ?>:</strong>
                <?= Loc::getMessage('MY_MODULE_STEP_4_DESC') ?>
            </li>
        </ul>
    </div>

    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #dee2e6;">
        <a href="/bitrix/admin/module_admin.php?lang=<?= LANGUAGE_ID ?>"
           style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;">
            ← <?= Loc::getMessage('MY_MODULE_BACK_TO_LIST') ?>
        </a>

        <a href="/bitrix/admin/my_module_settings.php?lang=<?= LANGUAGE_ID ?>"
           style="display: inline-block; padding: 10px 20px; background: #0069b4; color: white; text-decoration: none; border-radius: 4px;">
            ⚙ <?= Loc::getMessage('MY_MODULE_GO_TO_SETTINGS') ?>
        </a>
    </div>
</div>