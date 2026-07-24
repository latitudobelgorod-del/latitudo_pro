<?
// Динамическое меню «Все продукты»: разделы инфоблока «Каталог продукции» (IBLOCK_ID=3).
// Подключается компонентом bitrix:menu при USE_EXT=Y. Переопределяет статический
// .catalog.menu.php разделами из админки. Если модуль/разделы недоступны — остаётся
// статический список (фоллбэк).

// Статического .catalog.menu.php больше нет: он дублировал состав разделов и отставал
// от админки (в нём не было «Пергол»), а фолбэк, который врёт, хуже отсутствующего.
// Поэтому инициализируем список сами — раньше это делал он.
// CMenu::Init обрабатывает menu_ext независимо от базового файла, так что меню цело.
$aMenuLinks = array();

// Выборка разделов — общая с подвалом, в latitudoCatalogLandings()
// (local/php_interface/include/catalog-sections.php), чтобы список разделов
// не жил в двух местах и они не разъезжались.
if (function_exists('latitudoCatalogLandings')) {
    $CATALOG_IBLOCK_ID = 3;

    foreach (latitudoCatalogLandings($CATALOG_IBLOCK_ID) as $arSection) {
        $aMenuLinks[] = array(
            // Шаблон меню выводит TEXT как есть, поэтому экранируем здесь —
            // ровно это раньше делал за нас GetNext().
            htmlspecialcharsbx($arSection["NAME"]),
            $arSection["URL"],
            array(),
            array("FROM_IBLOCK" => "Y", "IBLOCK_ID" => $CATALOG_IBLOCK_ID, "SECTION_ID" => $arSection["ID"]),
            ""
        );
    }
}
?>
