<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
?>

<?php if (is_array($arResult['ITEMS']) && count($arResult['ITEMS']) > 0): ?>
<table class="table sale-personal-profile-list-container">
    <thead>
        <tr>
            <th>
                Дата
            </th>
            <th>
                Контрагент
            </th>
            <th>
                Артикул
            </th>
            <th>
                Номенклатура
            </th>
            <th>
                Характеристика номенклатуры
            </th>
            <th>
                Документ движения
            </th>
            <th>
                Количество
            </th>
            <th>
                Сумма
            </th>
            <th>
                Организация
            </th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($arResult['ITEMS'] as $order): ?>
        <tr>
            <td>
                <b><?= $order['BY_DATE_VALUE']?></b>
            </td>
            <td>
                <b><?= $order['COUNTERPARTY_VALUE']?></b>
            </td>
            <td>
                <b><?= $order['ARTICLE_VALUE']?></b>
            </td>
            <td>
                <b><?= $order['NOMENCLATURE_VALUE']?></b>
            </td>
            <td>
                <b><?= $order['CHAR_NOMENCLATURE_VALUE']?></b>
            </td>
            <td>
                <b><?= $order['MOTION_DOCUMENT_VALUE']?></b>
            </td>
            <td>
                <b><?= $order['QUANTITY_VALUE']?></b>
            </td>
            <td>
                <b><?= $order['AMOUNT_VALUE']?></b>
            </td>
            <td>
                <b><?= $order['BIND_USER_1C']['WORK_COMPANY']?></b>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php //echo '<pre>' . print_r($arResult, true) . '</pre>'; ?>
