<?
// Меню = якоря на блоки главной (по ТЗ и макету).
// Ссылки вида "/#anchor" — чтобы работали и с внутренних страниц.
// «Магазин в <город>» меняется по поддомену (данные из инфоблока «Магазины», см. region.php).
$shopCity = "Краснодаре";
if (function_exists('latitudoCurrentStore') && ($store = latitudoCurrentStore())) {
    $shopCity = $store['CITY_IN'];
}

$aMenuLinks = Array(
    Array("Преимущества", "/#advantages", Array(), Array(), ""),
    Array("Реализованные проекты", "/#projects", Array(), Array(), ""),
    Array("О компании", "/#about", Array(), Array(), ""),
    Array("Дилерам и партнёрам", "/#dealers", Array(), Array(), ""),
    Array("Магазин в ".$shopCity, "/#shop", Array(), Array(), ""),
    Array("Контакты", "/#contacts", Array(), Array(), ""),
);
?>
