<?
// Меню = якоря на блоки главной (по ТЗ и макету).
// Ссылки вида "/#anchor" — чтобы работали и с внутренних страниц.
// «Магазин в <город>» меняется по поддомену (данные из инфоблока «Магазины», см. region.php).
$shopCity = "Краснодаре";
if (function_exists('latitudoCurrentStore') && ($store = latitudoCurrentStore())) {
    $shopCity = $store['CITY_IN'];
}

// Состав пунктов — по макету раунда 4 (шапка 537:19144): 6 пунктов.
$aMenuLinks = Array();

// «Цены» ведёт на блок товаров (#catalog). На лендинге пустого раздела блока нет —
// не показываем и пункт меню (см. latitudoShowCatalogMenuItem в include/catalog-sections.php).
if (!function_exists('latitudoShowCatalogMenuItem') || latitudoShowCatalogMenuItem()) {
    $aMenuLinks[] = Array("Цены", "#catalog", Array(), Array(), "");
}

// «Преимущества» ведёт на блок #advantages. Он есть только там, где реально на странице
// (главная выставляет LATITUDO_HAS_ADVANTAGES до подключения шапки). Нет блока — нет пункта.
if (!empty($GLOBALS['LATITUDO_HAS_ADVANTAGES'])) {
    $aMenuLinks[] = Array("Преимущества", "#advantages", Array(), Array(), "");
}

$aMenuLinks = array_merge($aMenuLinks, Array(
    Array("Фото", "#projects", Array(), Array(), ""),
    Array("Отзывы", "#reviews", Array(), Array(), ""),
    Array("Магазин в ".$shopCity, "#visit-store", Array(), Array(), ""),
    Array("Контакты", "#contacts", Array(), Array(), ""),
));
?>
