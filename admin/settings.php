<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $APPLICATION, $USER;

$module_id = 'akatan.exporter_excel';

if (!Loader::includeModule($module_id)) {
    $APPLICATION->ThrowException(Loc::getMessage('AKATAN_EXCEL_NOT_INSTALLED'));
}

$APPLICATION->SetTitle(Loc::getMessage('AKATAN_EXCEL_SETTINGS_TITLE'));

// Проверка прав доступа
if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('AKATAN_EXCEL_ACCESS_DENIED'));
}

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid()) {
    if (isset($_POST['apply'])) {
        // Сохранение основных настроек
        if (isset($_POST['main_settings'])) {
            foreach ($_POST['main_settings'] as $key => $value) {
                \Bitrix\Main\Config\Option::set(
                    $module_id,
                    $key,
                    htmlspecialcharsbx($value)
                );
            }
        }

        // Сохранение прав доступа
        if (isset($_POST['permissions'])) {
            // Здесь логика сохранения прав доступа
        }

        CAdminMessage::ShowMessage([
            'MESSAGE' => Loc::getMessage('AKATAN_EXCEL_SETTINGS_SAVED'),
            'TYPE' => 'OK'
        ]);
    }
}

// Создаем табы
$aTabs = [
    [
        'DIV' => 'main_settings',
        'TAB' => Loc::getMessage('AKATAN_EXCEL_TAB_MAIN'),
        'TITLE' => Loc::getMessage('AKATAN_EXCEL_TAB_MAIN_TITLE')
    ],
    [
        'DIV' => 'permissions',
        'TAB' => Loc::getMessage('AKATAN_EXCEL_TAB_PERMISSIONS'),
        'TITLE' => Loc::getMessage('AKATAN_EXCEL_TAB_PERMISSIONS_TITLE')
    ]
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<?php $tabControl->Begin(); ?>

    <form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>">
        <?= bitrix_sessid_post() ?>

        <?php $tabControl->BeginNextTab(); ?>

        <tr>
            <td width="40%">
                <label for="setting1"><?= Loc::getMessage('AKATAN_EXCEL_SETTING_1') ?>:</label>
            </td>
            <td width="60%">
                <input type="text" name="main_settings[setting1]"
                       value="<?= htmlspecialcharsbx(\Bitrix\Main\Config\Option::get($module_id, 'setting1', '')) ?>"
                       size="50">
            </td>
        </tr>

        <tr>
            <td>
                <label for="setting2"><?= Loc::getMessage('AKATAN_EXCEL_SETTING_2') ?>:</label>
            </td>
            <td>
                <select name="main_settings[setting2]">
                    <option value="Y" <?= \Bitrix\Main\Config\Option::get($module_id, 'setting2', 'N') == 'Y' ? 'selected' : '' ?>>
                        <?= Loc::getMessage('AKATAN_EXCEL_YES') ?>
                    </option>
                    <option value="N" <?= \Bitrix\Main\Config\Option::get($module_id, 'setting2', 'N') == 'N' ? 'selected' : '' ?>>
                        <?= Loc::getMessage('AKATAN_EXCEL_NO') ?>
                    </option>
                </select>
            </td>
        </tr>

        <?php $tabControl->BeginNextTab(); ?>

        <tr>
            <td colspan="2">
                <?php
                // Здесь можно добавить компонент или форму для управления правами доступа
                // Например, через модуль main: CGroup::GetList
                ?>
                <p><?= Loc::getMessage('AKATAN_EXCEL_PERMISSIONS_INFO') ?></p>
            </td>
        </tr>

        <?php $tabControl->Buttons(); ?>

        <input type="submit" name="apply" value="<?= Loc::getMessage('AKATAN_EXCEL_SAVE') ?>" class="adm-btn-save">
        <input type="submit" name="default" value="<?= Loc::getMessage('AKATAN_EXCEL_RESTORE_DEFAULTS') ?>">

        <?php $tabControl->End(); ?>
    </form>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';