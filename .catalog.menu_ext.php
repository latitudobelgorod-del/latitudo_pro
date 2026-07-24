<?
// Динамическое меню «Все продукты»: разделы инфоблока «Каталог продукции» (IBLOCK_ID=3).
// Подключается компонентом bitrix:menu при USE_EXT=Y. Переопределяет статический
// .catalog.menu.php разделами из админки. Если модуль/разделы недоступны — остаётся
// статический список (фоллбэк).

// Выборка разделов — общая с подвалом, в latitudoCatalogLandings()
// (local/php_interface/include/catalog-sections.php), чтобы список разделов
// не жил в двух местах и они не разъезжались.
if (function_exists('latitudoCatalogLandings')) {
    $CATALOG_IBLOCK_ID = 3;
    $catalogLinks = array();

    foreach (latitudoCatalogLandings($CATALOG_IBLOCK_ID) as $arSection) {
        $catalogLinks[] = array(
            // Шаблон меню выводит TEXT как есть, поэтому экранируем здесь —
            // ровно это раньше делал за нас GetNext().
            htmlspecialcharsbx($arSection["NAME"]),
            $arSection["URL"],
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
