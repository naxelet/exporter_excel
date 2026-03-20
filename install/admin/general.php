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
use \Bitrix\Main\Diag\FileLogger;
use \Bitrix\Main\IO\Path;
use \Uploading0rders\Services\ImportResult;
use \Uploading0rders\Error\ImportException;

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
        'DIV' => 'edit_download',
        'TAB' => 'Параметры загрузки файла',
        'TITLE' => 'Параметры ипорта из файла'
    ],
    [
        'DIV' => 'edit_sales',
        'TAB' => 'Параметры продажи',
        'TITLE' => 'Параметры ипорта продажи'
    ],
    [
        'DIV' => 'edit_analysis',
        'TAB' => 'Параметры аналитики',
        'TITLE' => 'Параметры ипорта аналитики'
    ],
];
/**
 * end::список вкладок с настройками
 */

$iblockId = (int)trim(htmlspecialcharsbx(Option::get($module_id, 'IBLOCK_ID', '')));
$iblockSites = unserialize(Option::get($module_id, 'SELECTED_SITES', ''));
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/'.$module_id.'/';
$log_module_dir = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $module_id . '/logs/';
$log_path = $log_module_dir . 'import_' . date('Y-m-d') . '.log';
$logger = new FileLogger($log_path);
$logger->setLevel(\Psr\Log\LogLevel::DEBUG);
$tabControl = new \CAdminTabControl('tabControl', $aTabs, false);
$importResult = '';
$errorMessage = '';
$successMessage = '';
$message = null;

$update_existing = Option::get($module_id, 'UPDATE_EXISTING');
$update_existing_sale = Option::get($module_id, 'UPDATE_EXISTING_SALE');
$update_existing_analysis = Option::get($module_id, 'UPDATE_EXISTING_ANALYSIS');
$start_row = Option::get($module_id, 'START_ROW');
$start_row_sale = Option::get($module_id, 'START_ROW_SALE');
$start_row_analysis = Option::get($module_id, 'START_ROW_ANALYSIS');
$clear_columns = Option::get($module_id, 'CLEAR_COLUMNS');
$clear_columns_sale = Option::get($module_id, 'CLEAR_COLUMNS_SALE');
$clear_columns_analysis = Option::get($module_id, 'CLEAR_COLUMNS_ANALYSIS');
$clear_columns_index = Option::get($module_id, 'CLEAR_COLUMNS_INDEX');
$clear_columns_index_sale = Option::get($module_id, 'CLEAR_COLUMNS_INDEX_SALE');
$clear_columns_index_analysis = Option::get($module_id, 'CLEAR_COLUMNS_INDEX_ANALYSIS');
$clear_columns_num = Option::get($module_id, 'CLEAR_COLUMNS_NUM');
$clear_columns_num_sale = Option::get($module_id, 'CLEAR_COLUMNS_NUM_SALE');
$clear_columns_num_analysis = Option::get($module_id, 'CLEAR_COLUMNS_NUM_ANALYSIS');


$file = null;
$file_path = $module_id;
$allowedExtensions = ['xml', 'xlsx', 'xls', 'csv'];

// Создаем директорию для загрузок, если не существует
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$APPLICATION->SetTitle('Настройка импорта');

if ($request->isPost() && isset($request['apply']) && check_bitrix_sessid()) {
    // tab_1
    $start_row = (int)trim(htmlspecialcharsbx(strip_tags($request['start_row'])));
    $clear_columns = trim(htmlspecialcharsbx(strip_tags($request['clear_columns'])));
    $clear_columns_index = (int)trim(htmlspecialcharsbx(strip_tags($request['clear_columns_index'])));
    $clear_columns_num = (int)trim(htmlspecialcharsbx(strip_tags($request['clear_columns_num'])));

    Option::set($module_id, 'START_ROW', $start_row);
    Option::set($module_id, 'CLEAR_COLUMNS', $clear_columns);
    Option::set($module_id, 'CLEAR_COLUMNS_INDEX', $clear_columns_index);
    Option::set($module_id, 'CLEAR_COLUMNS_NUM', $clear_columns_num);

    // tab_2
    $start_row_sale = (int)trim(htmlspecialcharsbx(strip_tags($request['start_row_sale'])));
    $clear_columns_sale = trim(htmlspecialcharsbx(strip_tags($request['clear_columns_sale'])));
    $fill_path_sale = trim(htmlspecialcharsbx(strip_tags($request['fill_path_sale'])));
    $clear_columns_index_sale = (int)trim(htmlspecialcharsbx(strip_tags($request['clear_columns_index_sale'])));
    $clear_columns_num_sale = (int)trim(htmlspecialcharsbx(strip_tags($request['clear_columns_num_sale'])));

    $fill_path_sale = !empty($fill_path_sale) ?
        Path::convertRelativeToAbsolute($fill_path_sale) : Path::convertRelativeToAbsolute('/');

    Option::set($module_id, 'START_ROW_SALE', $start_row_sale);
    Option::set($module_id, 'CLEAR_COLUMNS_SALE', $clear_columns_sale);
    Option::set($module_id, 'CLEAR_COLUMNS_INDEX_SALE', $clear_columns_index_sale);
    Option::set($module_id, 'CLEAR_COLUMNS_NUM_SALE', $clear_columns_num_sale);
    Option::set($module_id, 'FILL_PATH_SALE', $fill_path_sale);

    // tab_3
    $start_row_analysis = (int)trim(htmlspecialcharsbx(strip_tags($request['start_row_analysis'])));
    $clear_columns_analysis = trim(htmlspecialcharsbx(strip_tags($request['clear_columns_analysis'])));
    $fill_path_analysis = trim(htmlspecialcharsbx(strip_tags($request['fill_path_analysis'])));
    $clear_columns_index_analysis = (int)trim(htmlspecialcharsbx(strip_tags($request['clear_columns_index_analysis'])));
    $clear_columns_num_analysis = (int)trim(htmlspecialcharsbx(strip_tags($request['clear_columns_num_analysis'])));

    $fill_path_analysis = !empty($fill_path_analysis) ?
        Path::convertRelativeToAbsolute($fill_path_analysis) : Path::convertRelativeToAbsolute('/');

    Option::set($module_id, 'START_ROW_ANALYSIS', $start_row_analysis);
    Option::set($module_id, 'CLEAR_COLUMNS_ANALYSIS', $clear_columns_analysis);
    Option::set($module_id, 'CLEAR_COLUMNS_INDEX_ANALYSIS', $clear_columns_index_analysis);
    Option::set($module_id, 'CLEAR_COLUMNS_NUM_ANALYSIS', $clear_columns_num_analysis);
    Option::set($module_id, 'FILL_PATH_ANALYSIS', $fill_path_analysis);

    if ($request['update_existing'] === 'Y') {
        Option::set($module_id, 'UPDATE_EXISTING', 'Y');
    } else {
        Option::set($module_id, 'UPDATE_EXISTING', '');
    }

    if ($request['update_existing_sale'] === 'Y') {
        Option::set($module_id, 'UPDATE_EXISTING_SALE', 'Y');
    } else {
        Option::set($module_id, 'UPDATE_EXISTING_SALE', '');
    }

    if ($request['update_existing_analysis'] === 'Y') {
        Option::set($module_id, 'UPDATE_EXISTING_ANALYSIS', 'Y');
    } else {
        Option::set($module_id, 'UPDATE_EXISTING_ANALYSIS', '');
    }
    unset(
        $skip_errors,
        $start_row,
        $clear_columns,
        $clear_columns_index,
        $fill_path,
        $fill_path_analysis,
        $clear_columns_num
    );
    LocalRedirect('/bitrix/admin/akatan.exporterexcel__general.php?mess=ok&lang=' . LANG . '&' . $tabControl->ActiveTabParam());
}

if ($request->isPost() && isset($request['import']) && check_bitrix_sessid()) {
    $mode = ($request['update_existing'] === 'Y') ? 'create_or_update' : 'create';
    $skip_errors = ($request['skip_errors'] === 'Y');
    $start_row = (int)trim(htmlspecialcharsbx(strip_tags($request['start_row'])));
    $clear_columns = trim(htmlspecialcharsbx(strip_tags($request['clear_columns'])));
    $clear_columns_index = (int)trim(htmlspecialcharsbx(strip_tags($request['clear_columns_index'])));
    $clear_columns_num = (int)trim(htmlspecialcharsbx(strip_tags($request['clear_columns_num'])));

    Option::set($module_id, 'START_ROW', $start_row);
    Option::set($module_id, 'CLEAR_COLUMNS', $clear_columns);
    Option::set($module_id, 'CLEAR_COLUMNS_INDEX', $clear_columns_index);
    Option::set($module_id, 'CLEAR_COLUMNS_NUM', $clear_columns_num);

    if ($request['update_existing'] === 'Y') {
        Option::set($module_id, 'UPDATE_EXISTING', 'Y');
    } else {
        Option::set($module_id, 'UPDATE_EXISTING', '');
    }

    if (!empty($_FILES['xml_file']['tmp_name'])) {
        $file = $_FILES['xml_file'];
        $file['MODULE_ID'] = $module_id;

        // Проверяем расширение файла
        $fileInfo = pathinfo($file['name']);

        if (!in_array(strtolower($fileInfo['extension']), $allowedExtensions)) {
            $errorMessage = Loc::getMessage('AKATAN_EXCEL_INVALID_FILE_TYPE');
        } elseif ($file['error'] != UPLOAD_ERR_OK) {
            $errorMessage = Loc::getMessage('AKATAN_EXCEL_UPLOAD_ERROR') . ': ' . $file['error'];
        } else {
            // Генерируем уникальное имя файла
            //$fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._\-]/', '', $file['name']);
            //$file_path .= $fileName;
            //$filePath = $uploadDir . $fileName;

            // Перемещаем загруженный файл
            $fileId = \CFile::SaveFile($file, $file_path);

            if ($fileId > 0) {
                // success
                try {
                    $inputFileName = $_SERVER['DOCUMENT_ROOT'] . \CFile::GetPath($fileId); //realpath($filePath);
                    $activeSheetIndex = 0;
                    $settings = [
                        'mode' => $mode,
                        'skip_errors' => $skip_errors,
                    ];
                    $mapper_xml = new ColumnExcelMapper();
                    $mapper_loading = new UploadingOrderMapper();
                    $excel_file = new ClientsHistoryExcel($inputFileName, $activeSheetIndex, $mapper_xml);
                    $excel_import = new ImportIblockService($iblockId);
                    $ib_processor = new InfoblockBatchProcessor($excel_import, $mapper_loading, $logger, $settings);
                    /*$ib_processor->setConfig([
                        'progress_callback' => function(int $processed, ImportResult $result) {
                            if ($processed % 100 === 0) {
                                echo "Прогресс: обработано {$processed} строк";
                            }
                        }
                    ]);*/
                    // ToDo::добавить в параметры формы соответсвие номера столбца и параметра
//                    $requiredColumns = ['NAME', 'ARTICLE', 'PRICE'];
//                    if (!$excel_file->validateStructure($requiredColumns)) {
//                        throw new \RuntimeException('Неверная структура файла');
//                    }
                    if ($clear_columns === 'Y') {
                        $excel_file->clearColums($clear_columns_index, $clear_columns_num);
                    }

                    $result = $ib_processor->import($excel_file->getRows($start_row));

                    Option::set($module_id, 'LAST_IMPORT_DATE', (new \DateTime())->format('Y-m-d H:i:s'));
                    Option::set($module_id, 'LAST_IMPORT_FILE', $inputFileName);
                    Option::set($module_id, 'LAST_IMPORT_COUNT', $result->getSuccessCount());
                    Option::set($module_id, 'LAST_IMPORT_STATS', $result->getStatsString());

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
                    }/* else {
                        $successMessage .= '<div style="color: darkgreen; padding: 20px; border: 1px solid darkgreen;">';
                        $successMessage .= $importResult;
                        $successMessage .= '</div>';
                    }*/
                    // если была нажата кнопка "Иппорта" - отправляем обратно на форму.
                    LocalRedirect('/bitrix/admin/akatan.exporterexcel__general.php?mess=ok&lang=' . LANG . '&' . $tabControl->ActiveTabParam());
                }  catch (\Throwable $error) {
                    $errorMessage .= '<div style="color: red; padding: 20px; border: 1px solid red;">';
                    $errorMessage .= '<h3>Ошибка импорта:</h3>';
                    $errorMessage .= '<p>' . htmlspecialchars($error->getMessage()) . '</p>';
                    $errorMessage .= '<pre>' . htmlspecialchars($error->getTraceAsString()) . '</pre>';
                    $errorMessage .= '</div>';

                     $logger->error('Ошибка импорта', [
                            'message' => $error->getMessage(),
                            'trace' => $error->getTraceAsString(),
                    ]);


                }
            } else {
                // error
                $errorMessage = Loc::getMessage('AKATAN_EXCEL_FILE_MOVE_ERROR');
            }
        }
    } else {
        $errorMessage = Loc::getMessage('AKATAN_EXCEL_NO_FILE_SELECTED');
    }
    unset(
        $mode,
        $skip_errors,
        $start_row,
        $clear_columns,
        $clear_columns_index,
        $clear_columns_num,
    );
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
if($request['mess'] === 'ok') {
    CAdminMessage::ShowMessage([
        'MESSAGE' => Loc::getMessage('AKATAN_EXCEL_IMPORT_SUCCESS'),
        'TYPE' => 'OK',
        'HTML' => true
    ]);
}
?>

<?php
$tabControl->Begin();
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
                        <?php
                        $tabControl->BeginNextTab();
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

                        <div style="margin-bottom: 15px; display: flex; flex-direction: column; gap: 15px;">
                            <div style="font-weight: bold; margin-bottom: 10px; display: block;">
                                <?= Loc::getMessage('AKATAN_EXCEL_IMPORT_SETTINGS') ?>
                            </div>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <input
                                        type="checkbox"
                                    <?= ($update_existing === 'Y') ? 'checked' : ''?>
                                        name="update_existing"
                                        value="Y"
                                >
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <?= Loc::getMessage('AKATAN_EXCEL_UPDATE_EXISTING') ?>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <input type="checkbox" name="skip_errors" value="Y">
                                    <?= Loc::getMessage('AKATAN_EXCEL_SKIP_ERRORS') ?>
                                </label>
                            </div>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <?= Loc::getMessage('AKATAN_EXCEL_START_ROW') . ': ' ?>
                                    <input type="text" name="start_row" value="<?= $start_row?>">
                                </label>
                            </div>
                            <div style="display: flex; gap: 15px; align-items: flex-start; flex-direction: column;">
                                <input type="checkbox" id="checkbox"
                                       name="clear_columns" value="Y"
                                       class="clear-columns__checkbox"
                                    <?= ($clear_columns === 'Y') ? 'checked' : ''?>
                                >
                                <label for="checkbox" class="clear-columns__btn">
                                    <div class="clear-columns__icon"></div>
                                    <?= Loc::getMessage('AKATAN_EXCEL_CLEAR_COLUMNS') . ': ' ?>
                                </label>
                                <div class="clear-columns__container">
                                    <?= Loc::getMessage('AKATAN_EXCEL_CLEAR_COLUMNS_INDEX') . ': ' ?>
                                    <input type="text" name="clear_columns_index" value="<?= $clear_columns_index; ?>">
                                    <?= Loc::getMessage('AKATAN_EXCEL_CLEAR_COLUMNS_NUM') . ': ' ?>
                                    <input type="text" name="clear_columns_num" value="<?= $clear_columns_num; ?>">
                                </div>
                            </div>
                        </div>
                        <div>
                            <!-- Информация о последнем импорте -->
                            <?php
                            $last_import_date = trim(htmlspecialcharsbx(Option::get($module_id, 'LAST_IMPORT_DATE')));
                            $last_import_file = Option::get($module_id, 'LAST_IMPORT_FILE');
                            $last_import_count = intval(Option::get($module_id, 'LAST_IMPORT_COUNT'));
                            $last_import_stats = Option::get($module_id, 'LAST_IMPORT_STATS');

                            if ($last_import_date): ?>
                                <div style="background: #e8f4fd; padding: 15px; border-radius: 6px; border-left: 4px solid #0069b4;">
                                    <h4 style="margin-top: 0; margin-bottom: 10px; color: #0069b4;">
                                        📅 <?= Loc::getMessage('AKATAN_EXCEL_LAST_IMPORT') ?>
                                    </h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                        <div>
                                            <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORT_DATE') ?>:</strong><br>
                                            <?= $last_import_date ?>
                                        </div>
                                        <div>
                                            <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORTED_ELEMENTS') ?>:</strong><br>
                                            <?= $last_import_count ?>
                                        </div>
                                        <div>
                                            <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORTED_STATS') ?>:</strong><br>
                                            <?= $last_import_stats ?>
                                        </div>
                                        <?php if ($last_import_file): ?>
                                            <div style="grid-column: span 2;">
                                                <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORT_FILE') ?>:</strong><br>
                                                <code style="background: #fff; padding: 2px 5px; border-radius: 3px;">
                                                    <?= trim(htmlspecialcharsbx($last_import_file)) ?>
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
                            <?php
                                    endif;
                                endif;
                            ?>
                            <?php
                            unset(
                                $last_import_date,
                                $last_import_file,
                                $last_import_count,
                                $last_import_stats,
                            );
                            ?>
                        </div>
                        <?php
                        $tabControl->endTab();
                        $tabControl->BeginNextTab();
                        ?>
                        <div style="display: flex; flex-direction: column; gap: 15px; align-items: center;margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 5px;">
                                <?= Loc::getMessage('AKATAN_EXCEL_FILL_PATH_SALE') . ': ' ?>
                                <input type="text" name="fill_path_sale" value="<?= str_replace($_SERVER['DOCUMENT_ROOT'], '', Option::get($module_id, 'FILL_PATH_SALE'));?>">
                            </label>
                        </div>

                        <div style="margin-bottom: 15px; display: flex; flex-direction: column; gap: 15px;">
                            <div style="font-weight: bold; margin-bottom: 10px; display: block;">
                                <?= Loc::getMessage('AKATAN_EXCEL_IMPORT_SETTINGS') ?>
                            </div>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <input
                                        type="checkbox"
                                        <?= ($update_existing_sale === 'Y') ? 'checked' : ''?>
                                        name="update_existing_sale"
                                        value="Y"
                                >
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <?= Loc::getMessage('AKATAN_EXCEL_UPDATE_EXISTING') ?>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <input type="checkbox" name="skip_errors_sale" value="Y">
                                    <?= Loc::getMessage('AKATAN_EXCEL_SKIP_ERRORS') ?>
                                </label>
                            </div>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <?= Loc::getMessage('AKATAN_EXCEL_START_ROW') . ': ' ?>
                                    <input type="text" name="start_row_sale" value="<?= $start_row_sale?>">
                                </label>
                            </div>
                            <div style="display: flex; gap: 15px; align-items: flex-start; flex-direction: column;">
                                <input type="checkbox"
                                       id="checkbox_sale"
                                       name="clear_columns_sale"
                                       value="Y"
                                       class="clear-columns__checkbox"
                                        <?= ($clear_columns_sale === 'Y') ? 'checked' : ''?>
                                >
                                <label for="checkbox_sale" class="clear-columns__btn">
                                    <div class="clear-columns__icon"></div>
                                    <?= Loc::getMessage('AKATAN_EXCEL_CLEAR_COLUMNS') . ': ' ?>
                                </label>
                                <div class="clear-columns__container">
                                    <?= Loc::getMessage('AKATAN_EXCEL_CLEAR_COLUMNS_INDEX') . ': ' ?>
                                    <input type="text" name="clear_columns_index_sale" value="<?= $clear_columns_index_sale; ?>">
                                    <?= Loc::getMessage('AKATAN_EXCEL_CLEAR_COLUMNS_NUM') . ': ' ?>
                                    <input type="text" name="clear_columns_num_sale" value="<?= $clear_columns_num_sale; ?>">
                                </div>
                            </div>
                        </div>
                        <div>
                            <!-- Информация о последнем импорте -->
                            <?php
                            $last_import_sale_date = trim(htmlspecialcharsbx(Option::get($module_id, 'LAST_IMPORT_SALE_DATE')));
                            $last_import_sale_file = trim(htmlspecialcharsbx(Option::get($module_id, 'LAST_IMPORT_SALE_FILE')));
                            $last_import_sale_count = intval(Option::get($module_id, 'LAST_IMPORT_SALE_COUNT'));
                            $last_import_sale_stats = Option::get($module_id, 'LAST_IMPORT_SALE_STATS');
                            $fill_path_sale = Option::get($module_id, 'FILL_PATH_SALE');

                            if ($last_import_sale_date): ?>
                                <div style="background: #e8f4fd; padding: 15px; border-radius: 6px; border-left: 4px solid #0069b4;">
                                    <h4 style="margin-top: 0; margin-bottom: 10px; color: #0069b4;">
                                        📅 <?= Loc::getMessage('AKATAN_EXCEL_LAST_IMPORT') ?>
                                    </h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                        <div>
                                            <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORT_DATE') ?>:</strong><br>
                                            <?= $last_import_sale_date ?>
                                        </div>
                                        <div>
                                            <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORTED_ELEMENTS') ?>:</strong><br>
                                            <?= $last_import_sale_count ?>
                                        </div>
                                        <div>
                                            <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORTED_STATS') ?>:</strong><br>
                                            <?= $last_import_sale_stats ?>
                                        </div>
                                        <?php if ($last_import_sale_file): ?>
                                            <div style="grid-column: span 2;">
                                                <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORT_FILE') ?>:</strong><br>
                                                <code style="background: #fff; padding: 2px 5px; border-radius: 3px;">
                                                    <?= $last_import_sale_file ?>
                                                </code>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?= $importResult?>
                                    </div>
                                    <div>
                                        <strong><?= Loc::getMessage('AKATAN_EXCEL_FILL_PATH') ?>:</strong><br>
                                        <?= $fill_path_sale?>
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
                                <?php
                                    endif;
                                endif;
                                ?>
                            <?php
                                unset(
                                    $last_import_sale_date,
                                    $last_import_sale_file,
                                    $last_import_sale_count,
                                    $last_import_sale_stats,
                                    $fill_path_sale,
                                );
                            ?>
                        </div>
                        <?php
                        $tabControl->endTab();
                        $tabControl->BeginNextTab();
                        ?>
                        <div style="display: flex; flex-direction: column; gap: 15px; align-items: center;margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 5px;">
                                <?= Loc::getMessage('AKATAN_EXCEL_FILL_PATH_ANALYSIS') . ': ' ?>
                                <input type="text" name="fill_path_analysis" value="<?= str_replace($_SERVER['DOCUMENT_ROOT'], '', Option::get($module_id, 'FILL_PATH_ANALYSIS'));?>">
                            </label>
                        </div>

                        <div style="margin-bottom: 15px; display: flex; flex-direction: column; gap: 15px;">
                            <div style="font-weight: bold; margin-bottom: 10px; display: block;">
                                <?= Loc::getMessage('AKATAN_EXCEL_IMPORT_SETTINGS') ?>
                            </div>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <input
                                        type="checkbox"
                                    <?= ($update_existing_analysis === 'Y') ? 'checked' : ''?>
                                        name="update_existing_analysis"
                                        value="Y"
                                >
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <?= Loc::getMessage('AKATAN_EXCEL_UPDATE_EXISTING') ?>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <input type="checkbox" name="skip_errors_analysis" value="Y">
                                    <?= Loc::getMessage('AKATAN_EXCEL_SKIP_ERRORS') ?>
                                </label>
                            </div>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 5px;">
                                    <?= Loc::getMessage('AKATAN_EXCEL_START_ROW') . ': ' ?>
                                    <input type="text" name="start_row_analysis" value="<?= $start_row_analysis?>">
                                </label>
                            </div>
                            <div style="display: flex; gap: 15px; align-items: flex-start; flex-direction: column;">
                                <input type="checkbox"
                                       id="checkbox_analysis"
                                       name="clear_columns_analysis"
                                       value="Y"
                                       class="clear-columns__checkbox"
                                    <?= ($clear_columns_analysis === 'Y') ? 'checked' : ''?>
                                >
                                <label for="checkbox_analysis" class="clear-columns__btn">
                                    <div class="clear-columns__icon"></div>
                                    <?= Loc::getMessage('AKATAN_EXCEL_CLEAR_COLUMNS') . ': ' ?>
                                </label>
                                <div class="clear-columns__container">
                                    <?= Loc::getMessage('AKATAN_EXCEL_CLEAR_COLUMNS_INDEX') . ': ' ?>
                                    <input type="text" name="clear_columns_index_analysis" value="<?= $clear_columns_index_analysis; ?>">
                                    <?= Loc::getMessage('AKATAN_EXCEL_CLEAR_COLUMNS_NUM') . ': ' ?>
                                    <input type="text" name="clear_columns_num_analysis" value="<?= $clear_columns_num_analysis; ?>">
                                </div>
                            </div>
                        </div>
                        <div>
                            <!-- Информация о последнем импорте -->
                            <?php
                            $last_import_analysis_date = trim(htmlspecialcharsbx(Option::get($module_id, 'LAST_IMPORT_ANALYSIS_DATE')));
                            $last_import_analysis_file = trim(htmlspecialcharsbx(Option::get($module_id, 'LAST_IMPORT_ANALYSIS_FILE')));
                            $last_import_analysis_count = intval(Option::get($module_id, 'LAST_IMPORT_ANALYSIS_COUNT'));
                            $last_import_analysis_stats = Option::get($module_id, 'LAST_IMPORT_ANALYSIS_STATS');
                            $fill_path_analysis = Option::get($module_id, 'FILL_PATH_ANALYSIS');

                            if ($last_import_analysis_date): ?>
                                <div style="background: #e8f4fd; padding: 15px; border-radius: 6px; border-left: 4px solid #0069b4;">
                                    <h4 style="margin-top: 0; margin-bottom: 10px; color: #0069b4;">
                                        📅 <?= Loc::getMessage('AKATAN_EXCEL_LAST_IMPORT') ?>
                                    </h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                        <div>
                                            <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORT_DATE') ?>:</strong><br>
                                            <?= $last_import_analysis_date ?>
                                        </div>
                                        <div>
                                            <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORTED_ELEMENTS') ?>:</strong><br>
                                            <?= $last_import_analysis_count ?>
                                        </div>
                                        <div>
                                            <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORTED_STATS') ?>:</strong><br>
                                            <?= $last_import_analysis_stats ?>
                                        </div>
                                        <?php if ($last_import_analysis_file): ?>
                                            <div style="grid-column: span 2;">
                                                <strong><?= Loc::getMessage('AKATAN_EXCEL_IMPORT_FILE') ?>:</strong><br>
                                                <code style="background: #fff; padding: 2px 5px; border-radius: 3px;">
                                                    <?= $last_import_analysis_file ?>
                                                </code>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?= $importResult?>
                                    </div>
                                    <div>
                                        <strong><?= Loc::getMessage('AKATAN_EXCEL_FILL_PATH') ?>:</strong><br>
                                        <?= $fill_path_analysis?>
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
                                <?php
                                endif;
                            endif; ?>
                            <?php
                            unset(
                                $last_import_analysis_date,
                                $last_import_analysis_file,
                                $last_import_analysis_count,
                                $last_import_analysis_stats,
                                $fill_path_analysis,
                            ); ?>
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

                            <button type="submit" name="apply" value="Y"
                                    style="padding: 10px 20px; background: #878383; color: #fffefe; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
                                <?= Loc::getMessage('AKATAN_EXCEL_APPLY_SETTINGS') ?>
                            </button>

                            <a href="/bitrix/admin/akatan_excel_settings.php?lang=<?= LANGUAGE_ID ?>"
                               style="margin-left: 10px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">
                                ← <?= Loc::getMessage('AKATAN_EXCEL_BACK_TO_SETTINGS') ?>
                            </a>
                        </div>
                    </form>
                </div>
        </div>
    </div>
</div>
<?php
//$tabControl->EndTab();
// завершаем интерфейс закладки
$tabControl->End();
?>
<style>
    .clear-columns__btn {
        display: flex;
        align-items: center;
        justify-content: center;
        /*width: 15px;*/
        /*height: 15px;*/
        cursor: pointer;
        transition: .4s;
        /*border: 2px solid #000;*/
        /*border-radius: 15%;*/
        gap: 15px;
    }
    .clear-columns__icon {
        display: block;
        position: relative;
        background: white;
        transition: .4s;
        width: 15px;
        height: 15px;
        border: 2px solid #000;
        border-radius: 15%;
    }
    .clear-columns__icon::after, .clear-columns__icon::before {
        content: "";
        display: none;
        position: absolute;
        background: #000;
        width: 55%;
        height: 2px;
        transition: .4s;
    }
    .clear-columns__icon::after {
        transform: rotate(-45deg);
        top: 50%;
        right: 0;
    }
    .clear-columns__icon::before {
        transform: rotate(45deg);
        top: 50%;
        left: 1px;
    }
    .clear-columns__container {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 0;
        opacity: 1;
        overflow: hidden;
    }
    .clear-columns__checkbox {
        display: none;
    }
    .clear-columns__checkbox:checked ~ .clear-columns__container {
        height: 100%;
        transition-delay: 0s;
    }
    .clear-columns__checkbox:checked ~ .clear-columns__btn .clear-columns__icon {
        display: block;
        background: transparent;
    }
    .clear-columns__checkbox:checked ~ .clear-columns__btn .clear-columns__icon::before, .clear-columns__checkbox:checked ~ .clear-columns__btn .clear-columns__icon::after {
        display: block;
    }
    .clear-columns__checkbox:checked ~ .clear-columns__btn .clear-columns__icon::after {
        transform: rotate(-45deg);
        -webkit-transform: rotate(-45deg);
    }
    .clear-columns__checkbox:checked ~ .clear-columns__btn .clear-columns__icon::before {
        transform: rotate(45deg);
        -webkit-transform: rotate(45deg);
    }
</style>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';?>