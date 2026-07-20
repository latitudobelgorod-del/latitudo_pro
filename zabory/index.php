<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "latitudo_products",
    [
        "IBLOCK_ID"          => (string)LATITUDO_CATALOG_IBLOCK_ID,
        // Раздел ищем по стабильному якорю: символьный код перегенерируется при
        // переименовании раздела в админке (см. include/catalog-sections.php)
        "SECTION_ID"         => latitudoCatalogSectionId("zabory"),
        // Блоки между hero и каталогом — порядок как в макете (Figma 537:24641)
        "AFTER_HERO_INCLUDE" => [
            [
                "PATH"  => "/include/zabory-story.php",
                "CLASS" => "story",
                "ID"    => "story",
                "NAME"  => "Блок «История одного забора»",
            ],
            [
                "PATH"  => "/include/zabory-benefits.php",
                "CLASS" => "benefits",
                "ID"    => "benefits",
                "NAME"  => "Блок «Заборы из ДПК»",
            ],
        ],
        "PROPERTY_CODE"      => ["GALLERY", "PRICE_CURRENT", "PRICE_OLD"],
        "ELEMENT_SORT_FIELD" => "SORT",
        "ELEMENT_SORT_ORDER" => "ASC",
        "PAGE_ELEMENT_COUNT" => "100",
        "CACHE_TYPE"         => "A",
        "CACHE_TIME"         => "3600",
    ]
);

// Акции месяца — по привязке к разделу и региону; без подходящих акций блок не выводится
latitudoShowPromosForSection("zabory");

// Слайдер с видео — включается галочкой UF_SHOW_VIDEO у раздела в админке;
// ролики берутся из множественного поля UF_VIDEO_SLIDER (ссылки YouTube)
latitudoShowVideosForSection("zabory");

// Квиз-опросник Marquiz — по привязке к разделу и региону; выключается «Активностью»
// элемента в админке. Нет подходящего квиза — блок не выводится.
latitudoShowMarquizForSection("zabory");

// Портфолио объектов — сквозной блок (табы = разделы инфоблока «Реализованные проекты»)
latitudoShowProjects();

// Отзывы — общий блок; скрывается галочкой UF_SHOW_REVIEWS у раздела в админке
latitudoShowReviewsForSection("zabory");

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
