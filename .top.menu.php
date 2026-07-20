<?
// Меню = якоря на блоки главной (по ТЗ и макету).
// Ссылки вида "/#anchor" — чтобы работали и с внутренних страниц.
// «Магазин в <город>» меняется по поддомену (данные из инфоблока «Магазины», см. region.php).
$shopCity = "Краснодаре";
if (function_exists('latitudoCurrentStore') && ($store = latitudoCurrentStore())) {
    $shopCity = $store['CITY_IN'];
}

// Состав пунктов — по макету раунда 4 (шапка 537:19144): 6 пунктов.
$aMenuLinks = Array(
    Array("Цены", "#catalog", Array(), Array(), ""),
    Array("Преимущества", "#advantages", Array(), Array(), ""),
    Array("Фото", "#projects", Array(), Array(), ""),
    Array("Отзывы", "#reviews", Array(), Array(), ""),
    Array("Магазин в ".$shopCity, "#visit-store", Array(), Array(), ""),
    Array("Контакты", "#contacts", Array(), Array(), ""),
);
?>
