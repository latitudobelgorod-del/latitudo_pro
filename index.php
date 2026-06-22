<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Latitudo — террасная доска, заборы и фасады из ДПК");
?>

<section class="hero">
    <div class="container">
        <h1 class="hero__title">Latitudo</h1>
        <p class="hero__subtitle">Террасная доска, заборы и фасады из древесно-полимерного композита</p>
        <a href="#catalog" class="hero__btn">Смотреть каталог</a>
    </div>
</section>

<section class="section" id="catalog">
    <div class="container">
        <h2 class="section__title">Каталог продукции</h2>
        <? $APPLICATION->IncludeComponent(
            "bitrix:catalog.section.list",
            "latitudo_catalog_grid",
            Array(
                "IBLOCK_TYPE"        => "latitudo_content",
                "IBLOCK_ID"          => "3",
                "SECTION_ID"         => "",
                "SECTION_CODE"       => "",
                "SECTION_URL"        => "/#SECTION_CODE#/", // ссылки вида /terrasnaya-doska/
                "COUNT_ELEMENTS"     => "N",
                "TOP_DEPTH"          => "1", // только разделы верхнего уровня
                "ADD_SECTIONS_CHAIN" => "N",
                "CACHE_TYPE"         => "A",
                "CACHE_TIME"         => "36000",
                "CACHE_GROUPS"       => "Y", // авто-сброс кэша при правке раздела
            ),
            false
        ); ?>
    </div>
</section>

<section class="section" id="advantages">
    <div class="container">
        <h2 class="section__title">Преимущества</h2>
        <? $APPLICATION->IncludeFile(
            "/include/advantages.php",
            Array(),
            Array("MODE" => "html", "NAME" => "Блок «Преимущества»")
        ); ?>
    </div>
</section>

<section class="section" id="projects">
    <div class="container">
        <h2 class="section__title">Реализованные проекты</h2>
        <? $APPLICATION->IncludeComponent(
            "bitrix:news.list",
            "latitudo_projects",
            Array(
                "IBLOCK_TYPE"            => "latitudo_content",
                "IBLOCK_ID"              => "4",
                "NEWS_COUNT"             => "100", // все сразу — фильтр на JS
                "SORT_BY1"               => "SORT",
                "SORT_ORDER1"            => "ASC",
                "SORT_BY2"               => "ID",
                "SORT_ORDER2"            => "DESC",
                "FIELD_CODE"             => Array("PREVIEW_PICTURE", "PREVIEW_TEXT", ""),
                "PROPERTY_CODE"          => Array("APPLICATION", "GALLERY", ""),
                "DETAIL_URL"             => "",
                "AJAX_MODE"              => "N",
                "DISPLAY_TOP_PAGER"      => "N",
                "DISPLAY_BOTTOM_PAGER"   => "N",
                "CACHE_TYPE"             => "A",
                "CACHE_TIME"             => "36000",
                "CACHE_GROUPS"           => "Y",
                "SET_TITLE"              => "N",
                "ADD_SECTIONS_CHAIN"     => "N",
                "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
                "PARENT_SECTION"         => "",
                "CHECK_DATES"            => "Y",
            ),
            false
        ); ?>
    </div>
</section>

<section class="section" id="about">
    <div class="container">
        <h2 class="section__title">О компании</h2>
        <? $APPLICATION->IncludeFile(
            "/include/about.php",
            Array(),
            Array("MODE" => "html", "NAME" => "Блок «О компании»")
        ); ?>
    </div>
</section>

<section class="section" id="reviews">
    <div class="container">
        <h2 class="section__title">Отзывы</h2>
        <p style="text-align:center; color:#999;">Блок «Отзывы» — будет подключён из инфоблока в фазе 3</p>
    </div>
</section>

<section class="section" id="contacts">
    <div class="container">
        <h2 class="section__title">Контакты</h2>
        <p style="text-align:center; color:#999;">Блок «Контакты» — будет добавлен в фазе 4</p>
    </div>
</section>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
