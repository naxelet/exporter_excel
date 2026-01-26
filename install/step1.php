<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Application;
use \Bitrix\Main\SiteTable;

Loc::loadMessages(__FILE__);

global $APPLICATION;

// Проверка CSRF токена
if (!check_bitrix_sessid()) {
    return;
}

$sites = [];
$request = Application::getInstance()->getContext()->getRequest();

$dbSites = SiteTable::getList([
    'filter' => ['ACTIVE' => 'Y'],
    'order' => ['SORT' => 'ASC']
]);

while ($site = $dbSites->fetch()) {
    $sites[$site['LID']] = $site['NAME'] . ' [' . $site['LID'] . ']';
}


$selectedSites = $request->getPost('selected_sites');

if (empty($selectedSites) && !empty($sites)) {
    // По умолчанию выбираем все сайты
    $selectedSites = array_keys($sites);
}
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="akatan.exporter_excel">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="step" value="2">

    <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 30px;">
        <h3 style="color: #0069b4; margin-top: 0; margin-bottom: 15px;">
            🌐 <?= Loc::getMessage('AKATAN_EXCEL_SELECT_SITES') ?>
        </h3>

        <div style="margin-bottom: 15px; color: #666;">
            <?= Loc::getMessage('AKATAN_EXCEL_SELECT_SITES_DESC') ?>
        </div>

        <?php if (!empty($sites)): ?>
            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; background: #fff;">
                <div style="margin-bottom: 10px;">
                    <input type="checkbox" id="select_all_sites"
                           onclick="let checkboxes = document.querySelectorAll('input[name=\"selected_sites[]\"]');
                    for(var i=0; i<checkboxes.length; i++) {
                    checkboxes[i].checked = this.checked;
                    }">
                    <label for="select_all_sites" style="font-weight: bold;">
                        <?= Loc::getMessage('AKATAN_EXCEL_SELECT_ALL') ?>
                    </label>
                </div>

                <?php foreach ($sites as $lid => $name): ?>
                    <div style="margin-bottom: 8px; padding: 5px 0; border-bottom: 1px solid #f1f1f1;">
                        <input type="checkbox"
                               name="selected_sites[]"
                               id="site_<?= htmlspecialcharsbx($lid) ?>"
                               value="<?= htmlspecialcharsbx($lid) ?>"
                            <?= in_array($lid, $selectedSites) ? 'checked' : '' ?>
                               style="margin-right: 10px;">
                        <label for="site_<?= htmlspecialcharsbx($lid) ?>" style="cursor: pointer;">
                            <?= htmlspecialcharsbx($name) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php if (count($sites) > 1): ?>
            <div style="margin-top: 10px; font-size: 12px; color: #6c757d;">
                <?= Loc::getMessage('AKATAN_EXCEL_SELECTED_COUNT') ?>:
                <span id="selected_count"><?= count($selectedSites) ?></span> / <?= count($sites) ?>
            </div>
        <?php endif; ?>

            <script>
                // Обновляем счетчик выбранных сайтов
                document.addEventListener('DOMContentLoaded', function() {
                    let checkboxes = document.querySelectorAll('input[name="selected_sites[]"]');
                    let counter = document.getElementById('selected_count');

                    function updateCounter() {
                        let selected = 0;
                        for(let i=0; i<checkboxes.length; i++) {
                            if(checkboxes[i].checked) selected++;
                        }
                        if(counter) counter.textContent = selected;
                    }

                    for(let i=0; i<checkboxes.length; i++) {
                        checkboxes[i].addEventListener('change', updateCounter);
                    }

                    updateCounter();
                });
            </script>

        <?php else: ?>
            <div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #ffc107;">
                ⚠ <?= Loc::getMessage('AKATAN_EXCEL_NO_SITES_FOUND') ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="background: #e8f4fd; padding: 20px; border-radius: 6px; margin-bottom: 30px;">
        <h3 style="color: #0069b4; margin-top: 0; margin-bottom: 15px;">
            📋 <?= Loc::getMessage('AKATAN_EXCEL_CREATING_IBLOCK') ?>
        </h3>

        <ul style="margin: 0; padding-left: 20px;">
            <li style="margin-bottom: 8px;">
                <strong><?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_TYPE') ?>:</strong>
                AKATAN_EXCEL_data
            </li>
            <li style="margin-bottom: 8px;">
                <strong><?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_NAME') ?>:</strong>
                Данные транзакций
            </li>
            <li style="margin-bottom: 8px;">
                <strong><?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_CODE') ?>:</strong>
                AKATAN_EXCEL_IBLOCK
            </li>
            <li style="margin-bottom: 8px;">
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
            </li>
        </ul>
    </div>

    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #dee2e6;">
        <a href="/bitrix/admin/module_admin.php?lang=<?= LANGUAGE_ID ?>"
           style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;">
            ← <?= Loc::getMessage('AKATAN_EXCEL_CANCEL') ?>
        </a>

        <button type="submit"
                name="install"
                style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
            ✅ <?= Loc::getMessage('AKATAN_EXCEL_INSTALL_BUTTON') ?>
        </button>
    </div>
</form>