<?
// Динамическое меню «Все продукты»: разделы инфоблока «Каталог продукции» (IBLOCK_ID=3).
// Подключается компонентом bitrix:menu при USE_EXT=Y. Переопределяет статический
// .catalog.menu.php разделами из админки. Если модуль/разделы недоступны — остаётся
// статический список (фоллбэк).

use Bitrix\Main\Loader;

if (Loader::includeModule('iblock')) {
    $CATALOG_IBLOCK_ID = 3;
    $catalogLinks = array();

    $rsSections = CIBlockSection::GetList(
        array("SORT" => "ASC", "NAME" => "ASC"),
        array(
            "IBLOCK_ID"     => $CATALOG_IBLOCK_ID,
            "ACTIVE"        => "Y",
            "GLOBAL_ACTIVE" => "Y",
            "DEPTH_LEVEL"   => 1, // только разделы верхнего уровня
        ),
        false,
        array("ID", "NAME", "CODE", "SECTION_PAGE_URL"),
        false
    );

    while ($arSection = $rsSections->GetNext()) {
        $code = trim((string)$arSection["CODE"]);
        // Ссылка по символьному коду (/terrasnaya-doska/); если кода нет — штатный URL раздела.
        $link = $code !== "" ? "/".$code."/" : $arSection["SECTION_PAGE_URL"];

        $catalogLinks[] = array(
            $arSection["NAME"],
            $link,
            array(),
            array("FROM_IBLOCK" => "Y", "IBLOCK_ID" => $CATALOG_IBLOCK_ID, "SECTION_ID" => $arSection["ID"]),
            ""
        );
    }

    // Подменяем статический список только если реально получили разделы.
    if (!empty($catalogLinks)) {
        $aMenuLinks = $catalogLinks;
    }
}
?>
