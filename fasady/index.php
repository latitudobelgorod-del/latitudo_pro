<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "latitudo_products",
    [
        "IBLOCK_ID"          => "3",
        "SECTION_CODE"       => "izdeliya-dpk",
        "DISPLAY_NAME"       => "Фасады", // подпись страницы: раздел исторически коден izdeliya-dpk
        "SET_TITLE"          => "N",      // не давать компоненту перетереть <title> именем раздела

        "PROPERTY_CODE"      => ["GALLERY", "PRICE_CURRENT", "PRICE_OLD"],
        "ELEMENT_SORT_FIELD" => "SORT",
        "ELEMENT_SORT_ORDER" => "ASC",
        "PAGE_ELEMENT_COUNT" => "100",
        "CACHE_TYPE"         => "A",
        "CACHE_TIME"         => "3600",
    ]
);

// Акции месяца — по привязке к разделу и региону; без подходящих акций блок не выводится
latitudoShowPromosForSection("izdeliya-dpk");

// Портфолио объектов — сквозной блок (табы = разделы инфоблока «Реализованные проекты»)
latitudoShowProjects();

// Отзывы — общий блок; скрывается галочкой UF_SHOW_REVIEWS у раздела в админке
latitudoShowReviewsForSection("izdeliya-dpk");

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
