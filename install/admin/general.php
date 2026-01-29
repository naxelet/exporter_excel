<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Application;
use \Bitrix\Main\HttpApplication;
use \Bitrix\Main\Config\Option;

global $APPLICATION;

Loc::loadMessages(__FILE__);

$MODULE_ID = 'akatan.exporterexcel';
$POST_RIGHT = $APPLICATION->GetGroupRight($MODULE_ID);
$aTabs = [
    [
        'TAB' => 'Параметры',
        'TITLE' => 'Параметры ипорта'
    ]
];

if ($POST_RIGHT == 'D') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule($MODULE_ID);
$request = HttpApplication::getInstance()->getContext()->getRequest();

$APPLICATION->SetTitle('Настройка импорта');
// отрисовываем форму
$tabControl = new \CAdminTabControl(
    'tabControl',
    $aTabs
);


require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
// подключаем модуль для того что бы был видем класс ORM
Loader::includeModule($MODULE_ID);
if ($request->isPost() && $request['Update'] && check_bitrix_sessid()) {
// если обновление прошло успешно
//    if ($res->isSuccess()) {
// перенаправим на новую страницу, в целях защиты от повторной отправки формы нажатием кнопки Обновить в браузере
//    }
// если обновление прошло не успешно
//    if (!$res->isSuccess()) {
// если в процессе сохранения возникли ошибки - получаем текст ошибки
//        if ($e = $APPLICATION->GetException())
//            $message = new CAdminMessage("Ошибка сохранения: ", $e);
//        else {
//            $mess = print_r($res->getErrorMessages(), true);
//            $message = new CAdminMessage("Ошибка сохранения: " . $mess);
//        }
//    }
}


// eсли есть сообщения об успешном сохранении, выведем их
if ($_REQUEST["mess"] == "ok") {
    CAdminMessage::ShowMessage(array("MESSAGE" => "Сохранено успешно", "TYPE" => "OK"));
}
// eсли есть сообщения об не успешном сохранении, выведем их
if ($message) {
    echo $message->Show();
}

?>
<form
        method="POST"
        action="<?= $APPLICATION->GetCurPage() ?>"
        ENCTYPE="multipart/form-data"
        name="post_form"
>
<?
// проверка идентификатора сессии
echo bitrix_sessid_post();
// отобразим заголовки закладок
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
    <tr>
        <td width="40%"><?= "Активность" ?></td>
        <td width="60%"><input type="checkbox" name="ACTIVE" value="Y" <? if ($str_ACTIVE == "Y") echo " checked" ?>>
        </td>
    </tr>
    <tr>
        <td>
            <label for="SITE"><?= "Сайты" ?></label>
        </td>
        <td>
            <select name="SITE[]" multiple>
                <option value="s1" <?= in_array('s1', $str_SITE) ? 'selected' : '' ?>>Для России</option>
                <option value="kz" <?= in_array('kz', $str_SITE) ? 'selected' : '' ?>>Для Казахстана</option>
            </select>
        </td>
    </tr>
    <tr>
        <td width="40%"><?= "Исключения" ?></td>
        <td width="60%"><textarea cols="50" rows="15" name="EXCEPTIONS"><?= $str_EXCEPTIONS ?></textarea></td>
    </tr>
    <tr>
        <td width="40%"><?= "Значение TARGET (self/blank)" ?></td>
        <td width="60%"><input type="text" name="TARGET" value="<?= $str_TARGET ?>"/></td>
    </tr>
<?
// выводит стандартные кнопки отправки формы
$tabControl->Buttons();
?>
    <input class="adm-btn-save" type="submit" name="save" value="Сохранить настройки"/>
<?
// завершаем интерфейс закладки
$tabControl->End();
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
?>