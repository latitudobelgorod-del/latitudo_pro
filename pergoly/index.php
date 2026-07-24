<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "latitudo_products",
    [
        "IBLOCK_ID"          => (string)LATITUDO_CATALOG_IBLOCK_ID,
        // Раздел ищем по стабильному якорю XML_ID (см. include/catalog-sections.php).
        "SECTION_ID"         => latitudoCatalogSectionId("pergoly"),
        // Слаг раздела для блоков «Марквиз» и «Акции месяца» внутри шаблона.
        "SECTION_SLUG"       => "pergoly",
        "PROPERTY_CODE"      => ["GALLERY", "PRICE_CURRENT", "PRICE_OLD"],
        "ELEMENT_SORT_FIELD" => "SORT",
        "ELEMENT_SORT_ORDER" => "ASC",
        "PAGE_ELEMENT_COUNT" => "100",
        "CACHE_TYPE"         => "A",
        "CACHE_TIME"         => "3600",
        // Разводим кэш по региону (hero-подзаголовок зависит от города).
        "REGION_CODE"        => latitudoCurrentRegionCode(),
    ]
);

// «С этими товарами покупают» — сопутствующие товары из UF-поля раздела (пусто → блока нет).
latitudoShowRelatedProducts("pergoly");

// «Компания Латитудо — производитель…» — статичный блок.
latitudoShowAboutProduction();

// Марквиз и «Акции месяца» выводятся ВНУТРИ компонента latitudo_products (см. SECTION_SLUG).

// Портфолио объектов — сквозной блок.
latitudoShowProjects();

// Отзывы — общий блок; скрывается галочкой UF_SHOW_REVIEWS у раздела в админке.
latitudoShowReviewsForSection("pergoly");

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
