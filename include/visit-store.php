<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?
$vsStore = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
if (!$vsStore) return;

$vsCityIn = htmlspecialcharsbx($vsStore['CITY_IN']);

// Фильтр по текущему поддомену — передаётся в компонент через глобальную переменную.
$GLOBALS['arVisitStoreFilter'] = ['=PROPERTY_SUBDOMAIN' => latitudoCurrentRegionCode()];
?>
<div class="visit-store">
    <div class="visit-store__head">
        <h2 class="visit-store__title">Посетите магазин в <?= $vsCityIn ?></h2>
        <? $APPLICATION->IncludeFile(
            "/include/visit-store-intro.php",
            Array(),
            Array("MODE" => "html", "NAME" => "Блок «Посетите магазин» — описание")
        ); ?>
    </div>

    <? $APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "latitudo_visit_store",
        Array(
            "IBLOCK_TYPE"          => "latitudo_content",
            "IBLOCK_ID"            => "6",
            "NEWS_COUNT"           => "1",
            "SORT_BY1"             => "ID",
            "SORT_ORDER1"          => "ASC",
            "FIELD_CODE"           => Array("NAME", ""),
            "PROPERTY_CODE"        => Array("GALLERY", "MANAGER_PHOTO", "MANAGER_NAME", "MANAGER_POSITION", ""),
            "FILTER_NAME"          => "arVisitStoreFilter",
            "DISPLAY_TOP_PAGER"    => "N",
            "DISPLAY_BOTTOM_PAGER" => "N",
            "CACHE_TYPE"           => "N",  // кэш отключён: результат зависит от поддомена
            "SET_TITLE"            => "N",
            "CHECK_DATES"          => "Y",
        ),
        false
    ); ?>
</div>
