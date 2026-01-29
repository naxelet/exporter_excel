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
        'OPTIONS' => [
            Loc::getMessage('EXCEL_IMPORTER_GENERAL_SITTING'),
            [
                'excel_importer_file',
                Loc::getMessage('EXCEL_IMPORTER_GENERAL_FILE'),
                '', // Значение по умолчанию
                [
                    'text',
                    50
                ]
            ],
        ],
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
    /*foreach ($aTabs as $aTab) {
        foreach ($aTab['OPTIONS'] as $arOption) {
            if (!is_array($arOption)) { // если это название секции
                continue;
            }
            if ($arOption['note']) { // если это примечание
                continue;
            }
            if ($request['apply']) { // сохраняем введенные настройки
                $optionValue = $request->getPost($arOption[0]);
                if ($arOption[0] == 'switch_on') {
                    if ($optionValue == '') {
                        $optionValue = 'N';
                    }
                }
                if ($arOption[0] == 'jquery_on') {
                    if ($optionValue == '') {
                        $optionValue = 'N';
                    }
                }
                Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(',', $optionValue) : $optionValue);
            } elseif ($request['default']) { // устанавливаем по умолчанию
                Option::set($module_id, $arOption[0], $arOption[2]);
            }
        }
    }*/
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
        <?php
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