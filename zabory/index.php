<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "latitudo_products",
    [
        "IBLOCK_ID"          => "3",
        "SECTION_CODE"       => "zabory",
        "PROPERTY_CODE"      => ["GALLERY", "PRICE_CURRENT", "PRICE_OLD"],
        "ELEMENT_SORT_FIELD" => "SORT",
        "ELEMENT_SORT_ORDER" => "ASC",
        "PAGE_ELEMENT_COUNT" => "100",
        "CACHE_TYPE"         => "A",
        "CACHE_TIME"         => "3600",
    ]
);

// Отзывы — общий блок; скрывается галочкой UF_SHOW_REVIEWS у раздела в админке
latitudoShowReviewsForSection("zabory");

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
