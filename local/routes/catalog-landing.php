<?php
/**
 * Диспетчер лендингов разделов каталога — ОДНА страница на все разделы.
 *
 * ЗАЧЕМ. Раньше под каждый раздел заводилась папка в корне сайта (/zabory/index.php
 * и ещё шесть таких же файлов, отличавшихся только слагом). Новый раздел в админке
 * без разработчика не открывался: страницы физически не существовало.
 * Теперь состав разделов живёт только в инфоблоке.
 *
 * КАК ПОПАДАЕМ СЮДА. .htaccess отдаёт всё несуществующее в bitrix/urlrewrite.php,
 * а правило в urlrewrite.php (последнее в списке, SORT 200) направляет односегментные
 * адреса вида /zabory/ на этот файл. Физические файлы и папки по-прежнему в приоритете:
 * mod_rewrite до urlrewrite не доходит, если путь существует.
 *
 * ЧЕМ ОТЛИЧАЮТСЯ РАЗДЕЛЫ. Ничем в коде — всё решает админка:
 *   • блоки между hero и товарами — включаемые области /include/<slug>-story.php
 *     и /include/<slug>-benefits.php, их подхватывает шаблон latitudo_products
 *     (файла нет → блока нет);
 *   • «Компания Латитудо» и «Как мы работаем» — галочки UF_SHOW_ABOUT
 *     и UF_SHOW_HOW_WE_WORK у раздела;
 *   • видео, отзывы, сопутствующие товары, квиз и акции гейтятся своими
 *     UF-полями и региональными привязками — как и раньше.
 * Порядок блоков — по макету (Figma, раунд 4).
 */

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

// Slug берём из адресной строки. GetCurDir() для канонического /zabory/ дал бы то же
// самое, но для адреса без завершающего слеша вернул бы '/', поэтому разбираем путь сами.
$path    = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$slug    = trim(urldecode($path), '/');
$section = latitudoCatalogSectionBySlug($slug);

// Канонический адрес раздела — со слешем на конце. Раньше на него редиректил mod_dir,
// потому что папка физически существовала; теперь делаем это сами, иначе внешние
// ссылки вида /zabory упрутся в 404. Слаг уже прошёл проверку в резолвере
// (иначе $section был бы null), поэтому подставляем его в адрес как есть.
if ($section && !str_ends_with($path, '/')) {
    LocalRedirect('/' . $slug . '/', false, '301 Moved Permanently');
}

if (!$section) {
    // Правило в urlrewrite ловит ЛЮБОЙ односегментный адрес, поэтому сюда попадают
    // и опечатки в URL. Отдаём ровно то же, что штатный /404.php: тот же статус,
    // тот же заголовок и та же карта сайта. Заново подключить /404.php нельзя —
    // он тянет bitrix/header.php, а шапка уже отрисована.
    CHTTP::SetStatus("404 Not Found");
    @define("ERROR_404", "Y");
    $APPLICATION->SetTitle("404 Not Found");
    $APPLICATION->IncludeComponent("bitrix:main.map", ".default", [
        "LEVEL"            => "3",
        "COL_NUM"          => "2",
        "SHOW_DESCRIPTION" => "Y",
        "SET_TITLE"        => "Y",
        "CACHE_TIME"       => "36000000",
    ]);
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");

    return;
}

$APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "latitudo_products",
    [
        "IBLOCK_ID"          => (string)LATITUDO_CATALOG_IBLOCK_ID,
        "SECTION_ID"         => (int)$section['ID'],
        // Слаг раздела нужен шаблону: по нему он ищет включаемые области после hero
        // и выводит «Марквиз» и «Акции месяца» между hero и сеткой товаров.
        "SECTION_SLUG"       => $slug,
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
latitudoShowRelatedProducts($slug);

// «Компания Латитудо — производитель…» — общая включаемая область /include/latitudo-about.php.
// По макету идёт сразу за «С этими товарами покупают» (Figma 537:19724 → 537:19731).
if ((string)($section['UF_SHOW_ABOUT'] ?? '') === '1') {
    latitudoShowAboutProduction();
}

// Слайдер с видео — галочка UF_SHOW_VIDEO у раздела; ролики из UF_VIDEO_SLIDER.
// Галочки нет → функция сама ничего не рисует.
latitudoShowVideosForSection($slug);

// Портфолио объектов — сквозной блок (табы = разделы инфоблока «Реализованные проекты»)
latitudoShowProjects();

// Отзывы — общий блок; скрывается галочкой UF_SHOW_REVIEWS у раздела в админке.
//
// Если у раздела включён блок «Как мы работаем», порядок хвоста по макету другой
// (Figma 537:24096): Посетите магазин → «Как мы работаем» → Отзывы. «Посетите магазин»
// рисуется из footer.php, то есть уже после содержимого страницы, поэтому оба блока
// регистрируем через хук — иначе они встали бы выше магазина.
if ((string)($section['UF_SHOW_HOW_WE_WORK'] ?? '') === '1') {
    latitudoAfterVisitStore(function () use ($slug) {
        latitudoShowHowWeWork();
        latitudoShowReviewsForSection($slug);
    });
} else {
    latitudoShowReviewsForSection($slug);
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
