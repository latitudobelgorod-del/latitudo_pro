<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "latitudo_products",
    [
        "IBLOCK_ID"          => (string)LATITUDO_CATALOG_IBLOCK_ID,
        // Раздел ищем по стабильному якорю: символьный код перегенерируется при
        // переименовании раздела в админке (см. include/catalog-sections.php)
        "SECTION_ID"         => latitudoCatalogSectionId("perila"),
        // Слаг раздела для блоков «Марквиз» и «Акции месяца» в шаблоне (между hero и товарами).
        "SECTION_SLUG"       => "perila",
        // Блок «Преимущества ограждений» между hero и каталогом (Figma 537:23893)
        "AFTER_HERO_INCLUDE" => "/include/perila-benefits.php",
        "PROPERTY_CODE"      => ["GALLERY", "PRICE_CURRENT", "PRICE_OLD"],
        "ELEMENT_SORT_FIELD" => "SORT",
        "ELEMENT_SORT_ORDER" => "ASC",
        "PAGE_ELEMENT_COUNT" => "100",
        "CACHE_TYPE"         => "A",
        "CACHE_TIME"         => "3600",
        // Разводим кэш по региону: hero-подзаголовок зависит от города (см. шаблон
        // latitudo_products и terrasnaya-doska/index.php).
        "REGION_CODE"        => latitudoCurrentRegionCode(),
    ]
);

// «С этими товарами покупают» — сопутствующие товары из UF-поля раздела
// UF_ELEMENTS_CATALOG. Поле пустое → блока нет (Figma 537:19724).
latitudoShowRelatedProducts("perila");

// Марквиз и «Акции месяца» выводятся ВНУТРИ компонента latitudo_products —
// между hero и сеткой товаров (см. template.php и параметр SECTION_SLUG). Здесь не вызываем.

// Портфолио объектов — сквозной блок (табы = разделы инфоблока «Реализованные проекты»)
latitudoShowProjects();

// Отзывы — общий блок; скрывается галочкой UF_SHOW_REVIEWS у раздела в админке
latitudoShowReviewsForSection("perila");

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
