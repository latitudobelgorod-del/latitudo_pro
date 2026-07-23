<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "latitudo_products",
    [
        "IBLOCK_ID"          => (string)LATITUDO_CATALOG_IBLOCK_ID,
        // Раздел ищем по стабильному якорю: символьный код перегенерируется при
        // переименовании раздела в админке (см. include/catalog-sections.php)
        "SECTION_ID"         => latitudoCatalogSectionId("terrasnaya-doska"),
        // Слаг раздела для блоков «Марквиз» и «Акции месяца» внутри шаблона
        // (выводятся между hero и сеткой товаров — см. latitudo_products/template.php).
        "SECTION_SLUG"       => "terrasnaya-doska",
        "PROPERTY_CODE"      => ["GALLERY", "PRICE_CURRENT", "PRICE_OLD"],
        "ELEMENT_SORT_FIELD" => "SORT",
        "ELEMENT_SORT_ORDER" => "ASC",
        "PAGE_ELEMENT_COUNT" => "100",
        "CACHE_TYPE"         => "A",
        "CACHE_TIME"         => "3600",
        // Кэш компонента общий для всех поддоменов (один SITE_ID). Hero-подзаголовок
        // зависит от города (зона доставки), поэтому разводим кэш по региону — иначе
        // строка закэшируется под первый открытый филиал. Параметр попадает в ключ кэша.
        "REGION_CODE"        => latitudoCurrentRegionCode(),
    ]
);

// «С этими товарами покупают» — сопутствующие товары из UF-поля раздела
// UF_ELEMENTS_CATALOG. Поле пустое → блока нет (Figma 537:19724).
latitudoShowRelatedProducts("terrasnaya-doska");

// «Компания Латитудо — производитель…» — статичный блок из /include/latitudo-about.php.
// По макету идёт сразу за «С этими товарами покупают» (Figma 537:19724 → 537:19731).
latitudoShowAboutProduction();

// Марквиз и «Акции месяца» выводятся ВНУТРИ компонента latitudo_products —
// между hero и сеткой товаров (см. template.php и параметр SECTION_SLUG выше),
// как в макете. Здесь их больше не вызываем, чтобы не задваивать.

// Портфолио объектов — сквозной блок (табы = разделы инфоблока «Реализованные проекты»)
latitudoShowProjects();

// Отзывы — общий блок; скрывается галочкой UF_SHOW_REVIEWS у раздела в админке
latitudoShowReviewsForSection("terrasnaya-doska");

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
