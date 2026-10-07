<?php
/**
 * Согласие на обработку персональных данных — отдельный документ, на него ведёт
 * галочка в форме заявки. Текст — /include/consent.php (по образцу latitudo.ru/info/consent/,
 * 07.10.2026). Адрес /consent — через правило «без .php» в .htaccess.
 */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Согласие на обработку персональных данных");
?>

<section class="doc-page">
    <div class="container">
        <div class="doc-page__text">
            <?php $APPLICATION->IncludeFile(
                "/include/consent.php",
                array(),
                array("MODE" => "html", "NAME" => "Согласие на обработку ПД")
            ); ?>
        </div>
    </div>
</section>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
