<?php
/**
 * Правила возврата товара и отказа от услуги (ЗоЗПП ст. 26.1). Текст — /include/returns.php
 * (по образцу latitudo.ru, 07.10.2026). Адрес /returns — через правило «без .php» в .htaccess.
 */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Правила возврата товара и отказа от услуги");
?>

<section class="doc-page">
    <div class="container">
        <div class="doc-page__text">
            <?php $APPLICATION->IncludeFile(
                "/include/returns.php",
                array(),
                array("MODE" => "html", "NAME" => "Правила возврата")
            ); ?>
        </div>
    </div>
</section>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
