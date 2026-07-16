<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Блок «Акции месяца» — обёртка над компонентом bitrix:news.list.
 * На лендинге раздела достаточно написать: <? latitudoShowPromosForSection('zabory'); ?>
 *
 * Источник — инфоблок с кодом PROMOS (создаётся скриптом tools/setup-promos.php).
 * ID инфоблока НЕ хардкодим: на локальной базе и на сервере он может отличаться.
 *
 * Какие акции попадают в блок (все условия одновременно):
 *   — активна и попадает в даты «Активен с/по» (CHECK_DATES у news.list);
 *   — регион акции пуст ИЛИ содержит магазин текущего поддомена (см. region.php);
 *   — привязка к разделам пуста ИЛИ содержит раздел текущего лендинга.
 * Нет подходящих акций — блок просто не выводится, страница не ломается.
 */

use Bitrix\Main\Loader;

const LATITUDO_PROMOS_IBLOCK_CODE = 'PROMOS';
const LATITUDO_PROMOS_IBLOCK_TYPE = 'latitudo_content';

/** ID инфоблока «Акции» по его коду, либо 0. Кэш в рамках запроса. */
function latitudoPromosIblockId(): int
{
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    if (!Loader::includeModule('iblock')) {
        return $id = 0;
    }
    $res = CIBlock::GetList([], [
        'TYPE'              => LATITUDO_PROMOS_IBLOCK_TYPE,
        'CODE'              => LATITUDO_PROMOS_IBLOCK_CODE,
        'ACTIVE'            => 'Y',
        'CHECK_PERMISSIONS' => 'N',
    ])->Fetch();

    return $id = $res ? (int)$res['ID'] : 0;
}

/** Акции на лендинге раздела каталога. Одна строка на странице. */
function latitudoShowPromosForSection(string $sectionCode): void
{
    latitudoShowPromos($sectionCode);
}

/**
 * Выводит секцию «Акции месяца» (заголовок + карусель баннеров).
 * $sectionCode — код раздела каталога, на лендинге которого стоит блок;
 * null — без фильтра по разделу (на случай сквозного использования).
 */
function latitudoShowPromos(?string $sectionCode = null): void
{
    global $APPLICATION;

    $iblockId = latitudoPromosIblockId();
    if (!$iblockId) {
        return;
    }

    // Регион: пустое свойство = акция для всех городов
    $store  = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
    $filter = [
        ['LOGIC' => 'OR', ['PROPERTY_REGION' => false], ['PROPERTY_REGION' => (int)($store['ID'] ?? 0)]],
    ];

    // Раздел: пустое свойство = акция для всех лендингов
    if ($sectionCode !== null) {
        $section = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => 3, 'CODE' => $sectionCode, 'ACTIVE' => 'Y'],
            false,
            ['ID']
        )->Fetch();
        $sectionId = $section ? (int)$section['ID'] : 0;
        $filter[]  = ['LOGIC' => 'OR', ['PROPERTY_SECTIONS' => false], ['PROPERTY_SECTIONS' => $sectionId]];
    }

    // news.list читает доп. фильтр из глобальной переменной по имени (FILTER_NAME)
    // и сам добавляет её содержимое в ключ кэша — разные регионы не перепутаются.
    $GLOBALS['latitudoPromosFilter'] = $filter;

    $APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "latitudo_promos",
        [
            "IBLOCK_TYPE"               => LATITUDO_PROMOS_IBLOCK_TYPE,
            "IBLOCK_ID"                 => (string)$iblockId,
            "NEWS_COUNT"                => "12",
            "SORT_BY1"                  => "SORT",
            "SORT_ORDER1"               => "ASC",
            "SORT_BY2"                  => "ID",
            "SORT_ORDER2"               => "DESC",
            "FILTER_NAME"               => "latitudoPromosFilter",
            "FIELD_CODE"                => ["PREVIEW_PICTURE", "DETAIL_TEXT", ""],
            "PROPERTY_CODE"             => [""],
            "DETAIL_URL"                => "",
            "AJAX_MODE"                 => "N",
            "DISPLAY_TOP_PAGER"         => "N",
            "DISPLAY_BOTTOM_PAGER"      => "N",
            "CACHE_TYPE"                => "A",
            "CACHE_TIME"                => "36000",
            "CACHE_GROUPS"              => "Y",
            "SET_TITLE"                 => "N",
            "ADD_SECTIONS_CHAIN"        => "N",
            "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
            "PARENT_SECTION"            => "",
            "CHECK_DATES"               => "Y",
        ],
        false
    );
}
