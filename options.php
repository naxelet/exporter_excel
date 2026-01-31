<?php
// подключим все необходимые файлы:
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\HttpApplication;

global $APPLICATION;

$module_id = 'akatan.exporterexcel'; // переменная $module_id обязательно в таком виде, иначе права доступа не сработают

Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT. '/modules/main/options.php');

// проверка доступа к модулю
$moduleGroupRight = $APPLICATION->GetGroupRight($module_id);
if ($moduleGroupRight < 'R') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/'.$module_id.'/include.php'); // инициализация модуля
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/'.$module_id.'/prolog.php'); // пролог модуля

Loader::includeModule($module_id);

$request = HttpApplication::getInstance()->getContext()->getRequest();

/**
 * start::список вкладок с настройками
 */
$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('EXCEL_IMPORTER_TAB_OPTION'),
        'TITLE' => Loc::getMessage('EXCEL_IMPORTER_TITLE_OPTION'),
    ],
    [
        'DIV' => 'edit2',
        'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'),
        'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS'),
    ]
];
/**
 * end::список вкладок с настройками
 */

$iblockId = trim(htmlspecialcharsbx(Option::get($module_id, 'IBLOCK_ID', '')));
$iblockSites = unserialize(Option::get($module_id, 'SELECTED_SITES', ''));

// сохранение формы
if ($request->isPost() && $request['Update'] && check_bitrix_sessid()) {
    if (isset($_POST['apply'])) {
        // Сохранение основных настроек
        if (isset($request['main_settings'])) {
            foreach ($request['main_settings'] as $key => $value) {
                Option::set(
                    $module_id,
                    $key,
                    trim(htmlspecialcharsbx(strip_tags($value)))
                );
            }
        }

        // Сохранение прав доступа
        if (isset($request['permissions'])) {
            // Здесь логика сохранения прав доступа
        }

        \CAdminMessage::ShowMessage([
            'MESSAGE' => Loc::getMessage('AKATAN_EXCEL_SETTINGS_SAVED'),
            'TYPE' => 'OK'
        ]);
    }
    LocalRedirect($APPLICATION->getCurPage() . '?mid=' . $request['mid'] . '&amp;lang=' . $request['lang']);
}
// ******************************************************************** //
//                ВЫВОД ФОРМЫ                                           //
// ******************************************************************** //

// не забудем разделить подготовку данных и вывод
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php'); // второй общий пролог

$tabControl = new \CAdminTabControl('tabControl', $aTabs);
?>
    <form
        method="post"
        action="<?= $APPLICATION->getCurPage()?>?mid=<?= htmlspecialcharsbx($request['mid'])?>&amp;lang=<?= $request['lang']?>"
        name="akatan_exporterexcel_sittings"
    >
        <?php
        // проверка идентификатора сессии
        echo bitrix_sessid_post();
        // отобразим заголовки закладок
        $tabControl->Begin();
        ?>
        <?php $tabControl->BeginNextTab(); ?>

        <tr>
            <td width="40%">
                <label for="iblock_id"><?= Loc::getMessage('AKATAN_EXCEL_SETTING_IBLOCK_ID') ?>:</label>
            </td>
            <td width="60%">
                <input type="text"
                       id="iblock_id"
                       name="main_settings[iblock_id]"
                       value="<?= $iblockId; ?>"
                       size="50"
                       disabled
                >
            </td>
        </tr>

        <?php if ($iblockId): ?>
            <tr>
                <td colspan="2">
                    <hr>
                    <h3><?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_INFO') ?></h3>
                </td>
            </tr>

            <tr>
                <td>
                    <label><?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_ID') ?>:</label>
                </td>
                <td>
                    <strong><?= $iblockId ?></strong>
                    <a href="/bitrix/admin/iblock_edit.php?ID=<?= $iblockId ?>&type=services&lang=<?= LANGUAGE_ID ?>"
                       style="margin-left: 10px; text-decoration: none;">
                        ✎ <?= Loc::getMessage('AKATAN_EXCEL_EDIT_IBLOCK') ?>
                    </a>
                </td>
            </tr>

            <tr>
                <td>
                    <label><?= Loc::getMessage('AKATAN_EXCEL_ASSIGNED_SITES') ?>:</label>
                </td>
                <td>
                    <?php if ($iblockSites && is_array($iblockSites)) :?>
                        <?php foreach ($iblockSites as $siteId):
                            $rsSite = \CSite::GetByID($siteId);
                            if ($arSite = $rsSite->Fetch()) {
                                echo trim(htmlspecialcharsbx($arSite['NAME'] . ' [' . $arSite['LID'] . ']')) . '<br>';
                            }
                        endforeach;?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endif; ?>

        <?php $tabControl->BeginNextTab(); ?>
        <?php
        require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php'); // для корректной
        //работы обязательно требуется объявление переменной $module_id
        /*foreach ($aTabs as $aTab):?>
            <?php
            if (isset($aTab['OPTIONS'])) {
                $tabControl->BeginNextTab();
                __AdmSettingsDrawList($module_id, $aTab['OPTIONS']);
                //echo '<pre>' . print_r($aTab,true) . '</pre>';
            }
            ?>
        <?php
            endforeach;
            $tabControl->BeginNextTab();
            require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php'); // для корректной
        //работы обязательно требуется объявление переменной $module_id
            $tabControl->Buttons();
        */?>
        <?php
        // завершение формы - вывод кнопок сохранения изменений
        $tabControl->Buttons();
        ?>

        <input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE')?>">
        <input type="reset" name="reset" value="<?= Loc::getMessage('MAIN_RESET')?>">

        <?php
        // завершаем интерфейс закладок
        $tabControl->End();

        // информационная подсказка
        echo BeginNote();
        ?>
        <span class="required">*</span><?php echo Loc::getMessage("REQUIRED_FIELDS")?>
        <?php echo EndNote();?>
    </form>
<?php
// завершение страницы
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
?>