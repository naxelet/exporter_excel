<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\HttpApplication;
use \Bitrix\Main\Application;
use \Uploading0rders\ClientsHistoryExcel;
use \Uploading0rders\ImportIblockService;
use \Uploading0rders\Mapper\ColumnExcelMapper;
use \Uploading0rders\Mapper\UploadingOrderMapper;
use \Uploading0rders\Processor\InfoblockBatchProcessor;
use \Uploading0rders\Services\ImportResult;

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
        'TAB' => 'Параметры',
        'TITLE' => 'Параметры ипорта'
    ]
];
/**
 * end::список вкладок с настройками
 */

$iblockId = (int)trim(htmlspecialcharsbx(Option::get($module_id, 'IBLOCK_ID', '')));
$iblockSites = unserialize(Option::get($module_id, 'SELECTED_SITES', ''));
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/'.$module_id.'/';
$tabControl = new \CAdminTabControl('tabControl', $aTabs);
$importResult = '';
$errorMessage = '';
$successMessage = '';
$message = null;

// Создаем директорию для загрузок, если не существует
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$APPLICATION->SetTitle('Настройка импорта');

if ($request->isPost() && isset($request['import']) && check_bitrix_sessid()) {
    $mode = ($request['update_existing'] === 'Y') ? 'update' : 'create';
    $skip_errors = ($request['skip_errors'] === 'Y');
    if (!empty($_FILES['xml_file']['tmp_name'])) {
        $file = $_FILES['xml_file'];

        // Проверяем расширение файла
        $fileInfo = pathinfo($file['name']);
        $allowedExtensions = ['xml', 'xlsx', 'xls', 'csv'];

        if (!in_array(strtolower($fileInfo['extension']), $allowedExtensions)) {
            $errorMessage = Loc::getMessage('AKATAN_EXCEL_INVALID_FILE_TYPE');
        } elseif ($file['error'] != UPLOAD_ERR_OK) {
            $errorMessage = Loc::getMessage('AKATAN_EXCEL_UPLOAD_ERROR') . ': ' . $file['error'];
        } else {
            // Генерируем уникальное имя файла
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._\-]/', '', $file['name']);
            $filePath = $uploadDir . $fileName;

            // Перемещаем загруженный файл
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                try {
                    $inputFileName =  realpath($filePath);
                    $logPath = realpath($_SERVER['DOCUMENT_ROOT'] . '/upload/logs/import_' . date('Y-m-d') . '.log');
                    $activeSheetIndex = 0;
                    $settings = [
                        'mode' => $mode,
                        'skip_errors' => $skip_errors,
                    ];
                    $mapper_xml = new ColumnExcelMapper();
                    $mapper_loading = new UploadingOrderMapper();
                    $excel_file = new ClientsHistoryExcel($inputFileName, $activeSheetIndex, $mapper_xml);
                    $excel_import = new ImportIblockService($iblockId);
                    $ib_processor = new InfoblockBatchProcessor($excel_import, $mapper_loading, $settings);
                    $ib_processor->setConfig([
                        'progress_callback' => function(int $processed, ImportResult $result) {
                            if ($processed % 100 === 0) {
                                echo "Прогресс: обработано {$processed} строк";
                            }
                        }
                    ]);
                    // ToDo::добавить в параметры формы соответсвие номера столбца и параметра
                    // ToDo::валидация файла
//                    $requiredColumns = ['NAME', 'ARTICLE', 'PRICE'];
//                    if (!$excel_file->validateStructure($requiredColumns)) {
//                        throw new \RuntimeException('Неверная структура файла');
//                    }
                    // ToDo::добавить в параметры формы начальную строку в файле
                    $result = $ib_processor->import($excel_file->getRows(605));

                    Option::set($module_id, 'LAST_IMPORT_DATE', (new \DateTime())->format('Y-m-d H:i:s'));
                    Option::set($module_id, 'LAST_IMPORT_FILE', $inputFileName);
                    Option::set($module_id, 'LAST_IMPORT_COUNT', $result->getSuccessCount());

                    // Вывод результатов
                    $importResult .= '<h2>Результаты импорта</h2>';
                    $importResult .= '<pre>';
                    $importResult .= $result->getStatsString();
                    $importResult .= '</pre>';

                    if (!$result->isSuccess()) {
                        $errorMessage .= '<h3>Ошибки:</h3>';
                        $errorMessage .= '<ul>';
                        foreach ($result->errors as $error) {
                            $errorMessage .= "<li>Строка {$error['row']}: {$error['message']}</li>";
                        }
                        $errorMessage .= '</ul>';
                    } else {
                        $successMessage = 'Сохранено успешно';
                    }
                    // если была нажата кнопка "Иппорта" - отправляем обратно на форму.
                    LocalRedirect('/bitrix/admin/akatan.exporterexcel__general.php?mess=ok&lang=' . LANG . '&' . $tabControl->ActiveTabParam());
                }  catch (\Throwable $error) {
                    $errorMessage .= '<div style="color: red; padding: 20px; border: 1px solid red;">';
                    $errorMessage .= '<h3>Ошибка импорта:</h3>';
                    $errorMessage .= '<p>' . htmlspecialchars($error->getMessage()) . '</p>';
                    $errorMessage .= '<pre>' . htmlspecialchars($error->getTraceAsString()) . '</pre>';
                    $errorMessage .= '</div>';

//                     log->error('Ошибка импорта', [
//                            'message' => $error->getMessage(),
//                            'trace' => $error->getTraceAsString(),
//                        ]);}

                }
            } else {
                $errorMessage = Loc::getMessage('AKATAN_EXCEL_FILE_MOVE_ERROR');
            }
        }
    } else {
        $errorMessage = Loc::getMessage('AKATAN_EXCEL_NO_FILE_SELECTED');
    }
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
    echo 'Результат импорта: ' . $importResult;
}


// eсли есть сообщения об успешном сохранении, выведем их
//if ($_REQUEST['mess'] === 'ok') {
//    \CAdminMessage::ShowMessage(['MESSAGE' => 'Сохранено успешно', 'TYPE' => 'OK']);
//}

// ******************************************************************** //
//                ВЫВОД ФОРМЫ                                           //
// ******************************************************************** //

// не забудем разделить подготовку данных и вывод
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php'); // второй общий пролог
// Отображаем сообщения об ошибках/успехе
if ($errorMessage) {
    CAdminMessage::ShowMessage([
        'MESSAGE' => $errorMessage,
        'TYPE' => 'ERROR',
        'HTML' => true
    ]);
}

if ($successMessage) {
    CAdminMessage::ShowMessage([
        'MESSAGE' => $successMessage,
        'TYPE' => 'OK',
        'HTML' => true
    ]);
}
?>

<?php
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
<div style="max-width: 1000px; margin: 20px auto;">
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h1 style="color: #0069b4; margin-top: 0; margin-bottom: 20px;">
            ⬆ <?= Loc::getMessage('AKATAN_EXCEL_IMPORT_TITLE') ?>
        </h1>

        <div style="display: flex; gap: 30px;">
            <!-- Форма загрузки файла -->
            <div style="flex: 1;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                    <h3 style="color: #0069b4; margin-top: 0; margin-bottom: 15px;">
                        📤 <?= Loc::getMessage('AKATAN_EXCEL_UPLOAD_FILE') ?>
                    </h3>

                    <form
                            method="post"
                            action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>"
                            enctype="multipart/form-data"
                            name="import_form"
                    >
                        <?php
                        // проверка идентификатора сессии
                        echo bitrix_sessid_post();
                        ?>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">
                                <?= Loc::getMessage('AKATAN_EXCEL_SELECT_FILE') ?>
                            </label>
                            <input type="file" name="xml_file" accept=".xml,.xlsx,.xls,.csv"
                                   style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
                            <div style="margin-top: 5px; font-size: 12px; color: #6c757d;">
                                <?= Loc::getMessage('AKATAN_EXCEL_ALLOWED_FORMATS') ?>
                            </div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: bold; margin-bottom: 10px; display: block;">
                                <?= Loc::getMessage('AKATAN_EXCEL_IMPORT_SETTINGS') ?>
                            </label>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <input type="checkbox" name="update_existing" value="Y">
                                    <?= Loc::getMessage('AKATAN_EXCEL_UPDATE_EXISTING') ?>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <input type="checkbox" name="skip_errors" value="Y">
                                    <?= Loc::getMessage('AKATAN_EXCEL_SKIP_ERRORS') ?>
                                </label>
                            </div>
                        </div>
                        <?php
                        // выводит стандартные кнопки отправки формы
                        $tabControl->Buttons();
                        ?>
                        <div style="margin-top: 20px;">
                            <button type="submit" name="import" value="Y"
                                    style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
                                🚀 <?= Loc::getMessage('AKATAN_EXCEL_START_IMPORT') ?>
                            </button>

                            <a href="/bitrix/admin/akatan_excel_settings.php?lang=<?= LANGUAGE_ID ?>"
                               style="margin-left: 10px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">
                                ← <?= Loc::getMessage('AKATAN_EXCEL_BACK_TO_SETTINGS') ?>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Информация о последнем импорте -->
                <?php
                $lastImportDate = Option::get($module_id, 'LAST_IMPORT_DATE');
                $lastImportFile = Option::get($module_id, 'LAST_IMPORT_FILE');
                $lastImportCount = Option::get($module_id, 'LAST_IMPORT_COUNT');

                if ($lastImportDate): ?>
                    <div style="background: #e8f4fd; padding: 15px; border-radius: 6px; border-left: 4px solid #0069b4;">
                        <h4 style="margin-top: 0; margin-bottom: 10px; color: #0069b4;">
                            📅 <?= Loc::getMessage('AKATAN_EXCEL_LAST_IMPORT') ?>
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORT_DATE') ?>:</strong><br>
                                <?= htmlspecialcharsbx($lastImportDate) ?>
                            </div>
                            <div>
                                <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORTED_ELEMENTS') ?>:</strong><br>
                                <?= intval($lastImportCount) ?>
                            </div>
                            <?php if ($lastImportFile): ?>
                                <div style="grid-column: span 2;">
                                    <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORT_FILE') ?>:</strong><br>
                                    <code style="background: #fff; padding: 2px 5px; border-radius: 3px;">
                                        <?= htmlspecialcharsbx($lastImportFile) ?>
                                    </code>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?= $importResult?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Информация о структуре файла -->
            <div style="flex: 1;">
                <!-- Информация о текущем инфоблоке -->
                <?php if ($iblockId && Loader::includeModule('iblock')):
                    $res = CIBlock::GetByID($iblockId);
                    if ($arIBlock = $res->GetNext()): ?>
                        <div style="background: #d4edda; padding: 15px; border-radius: 6px; margin-top: 20px; border-left: 4px solid #28a745;">
                            <h4 style="margin-top: 0; margin-bottom: 10px; color: #155724;">
                                📊 <?= Loc::getMessage('AKATAN_EXCEL_CURRENT_IBLOCK') ?>
                            </h4>
                            <div>
                                <strong><?= Loc::getMessage('AKATAN_EXCEL_IBLOCK_NAME') ?>:</strong>
                                <?= htmlspecialcharsbx($arIBlock['NAME']) ?><br>
                                <strong>ID:</strong> <?= $iblockId ?><br>
                                <strong><?= Loc::getMessage('AKATAN_EXCEL_TOTAL_ELEMENTS') ?>:</strong>
                                <?php
                                $elementCount = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId], []);
                                echo $elementCount;
                                ?>
                            </div>
                            <div style="margin-top: 10px;">
                                <a href="/bitrix/admin/iblock_element_admin.php?IBLOCK_ID=<?= $iblockId ?>&type=<?= htmlspecialcharsbx($arIBlock['IBLOCK_TYPE_ID']) ?>&lang=<?= LANGUAGE_ID ?>"
                                   style="display: inline-block; padding: 5px 10px; background: #28a745; color: white; text-decoration: none; border-radius: 3px; font-size: 14px;">
                                    📋 <?= Loc::getMessage('AKATAN_EXCEL_VIEW_ELEMENTS') ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$tabControl->EndTab();
// завершаем интерфейс закладки
$tabControl->End();
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
?>