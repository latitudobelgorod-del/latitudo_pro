<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "latitudo_products",
    [
        "IBLOCK_ID"          => (string)LATITUDO_CATALOG_IBLOCK_ID,
        // Раздел ищем по стабильному якорю: символьный код перегенерируется при
        // переименовании раздела в админке (см. include/catalog-sections.php)
        "SECTION_ID"         => latitudoCatalogSectionId("stupeni"),
        "PROPERTY_CODE"      => ["GALLERY", "PRICE_CURRENT", "PRICE_OLD"],
        "ELEMENT_SORT_FIELD" => "SORT",
        "ELEMENT_SORT_ORDER" => "ASC",
        "PAGE_ELEMENT_COUNT" => "100",
        "CACHE_TYPE"         => "A",
        "CACHE_TIME"         => "3600",
    ]
);

// «С этими товарами покупают» — сопутствующие товары из UF-поля раздела
// UF_ELEMENTS_CATALOG. Поле пустое → блока нет (Figma 537:19724).
latitudoShowRelatedProducts("stupeni");

// Акции месяца — по привязке к разделу и региону; без подходящих акций блок не выводится
latitudoShowPromosForSection("stupeni");

// Портфолио объектов — сквозной блок (табы = разделы инфоблока «Реализованные проекты»)
latitudoShowProjects();

// По макету (Figma 537:24096) на этой странице порядок хвоста такой:
//     Посетите магазин → «Как мы работаем» (6 шагов) → Отзывы
// «Посетите магазин» рисуется из footer.php, то есть уже после содержимого страницы,
// поэтому оба блока регистрируем через хук — иначе они встали бы выше магазина.
// Отзывы по-прежнему скрываются галочкой UF_SHOW_REVIEWS у раздела в админке.
latitudoAfterVisitStore(function () {
    latitudoShowHowWeWork();
    latitudoShowReviewsForSection("stupeni");
});

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
