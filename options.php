<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\HttpApplication;

$module_id = 'akatan.exporterexcel'; // переменная $module_id обязательно в таком виде, иначе права доступа не сработают

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT. '/modules/main/options.php');
Loc::loadMessages(__FILE__);

// проверка доступа к модулю
if ($APPLICATION->GetGroupRight($module_id) < 'S') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

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

// сохранение формы
if ($request->isPost() && $request['Update'] && check_bitrix_sessid()) {
    foreach ($aTabs as $aTab) {
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
    }
    LocalRedirect($APPLICATION->getCurPage().'?mid=' . $request['mid'] . '&amp;lang=' . $request['lang']);

    // визуальный вывод

$tabControl = new CAdminTabControl('tabControl', $aTabs);
$tabControl->Begin();
?>
    <form
        method="post"
        action="<?= $APPLICATION->getCurPage()?>?mid=<?= htmlspecialcharsbx($request['mid'])?>&amp;lang=<?= $request['lang']?>"
        name="akatan_general_sittings"
    >
        <?php foreach ($aTabs as $aTab):?>
            <?php if ($aTab['OPTIONS']) {
                $tabControl->BeginNextTab();
                __AdmSittingsDrawList($module_id, $aTab['OPTIONS']);
            }?>
        <?php
            endforeach;
            $tabControl->BeginNextTab();
            require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php'); // для корректной
        //работы обязательно требуется объявление переменной $module_id
            $tabControl->Buttons();
        ?>

        <input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE')?>">
        <input type="reset" name="reset" value="<?= Loc::getMessage('MAIN_RESET')?>">
        <?= bitrix_sessid_post()?>
    </form>
<?php
$tabControl->End();
}