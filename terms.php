<?php
/**
 * Пользовательское соглашение — ОТДЕЛЬНАЯ СТРАНИЦА.
 * Устройство и причина — те же, что у /policy.php (см. комментарий там):
 * документ должен открываться по прямому адресу, а не только во всплывающем окне.
 * Текст лежит в /include/terms.php и питает и страницу, и окно в подвале.
 */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Пользовательское соглашение");
?>

<section class="doc-page">
    <div class="container">
        <div class="doc-page__text">
            <?php $APPLICATION->IncludeFile(
                "/include/terms.php",
                array(),
                array("MODE" => "html", "NAME" => "Пользовательское соглашение")
            ); ?>
        </div>
    </div>
</section>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
