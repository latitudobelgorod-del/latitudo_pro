<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Разделы каталога: связь «папка лендинга → раздел инфоблока».
 *
 * ЗАЧЕМ. Раньше страницы лендингов ссылались на раздел по символьному коду
 * ("SECTION_CODE" => "izdeliya-dpk"). При переименовании раздела в админке Битрикс
 * перегенерирует символьный код из нового названия — код в файлах устаревает молча,
 * и страница отдаёт «Раздел не найден» (так сломался /fasady/ 2026-07-18).
 *
 * КАК ТЕПЕРЬ. Якорь — XML_ID раздела: админка его не трогает при переименовании.
 * XML_ID = slug папки лендинга (/fasady/ → 'fasady'). Страницы просят SECTION_ID,
 * полученный через latitudoCatalogSectionId('fasady').
 *
 * Фолбэк: если XML_ID ещё не проставлен (новая база, забыли прогнать
 * tools/migrate-section-xmlid.php) — ищем по CODE = slug, как раньше.
 * Проставить якоря: php tools/migrate-section-xmlid.php
 */

use Bitrix\Main\Loader;

const LATITUDO_CATALOG_IBLOCK_ID = 3;

/**
 * Список лендингов: slug папки → символьные коды раздела, которые встречались
 * исторически. Нужен только миграции (проставить XML_ID) — рантайм ходит по XML_ID.
 * Первый код в списке — актуальный.
 */
const LATITUDO_CATALOG_LANDINGS = [
    'terrasnaya-doska'    => ['terrasnaya-doska'],
    'stroitelstvo-terras' => ['stroitelstvo-terras'],
    'zabory'              => ['zabory'],
    'perila'              => ['perila'],
    'stupeni'             => ['stupeni'],
    'fasady'              => ['fasady', 'izdeliya-dpk'],
    'pergoly'             => ['pergoly'],
];

/**
 * ID раздела каталога по slug лендинга. 0 — раздел не найден.
 * Кэш в рамках запроса: одна страница дёргает резолвер 3 раза (каталог, акции, отзывы).
 */
function latitudoCatalogSectionId(string $slug, int $iblockId = LATITUDO_CATALOG_IBLOCK_ID): int
{
    static $cache = [];
    $key = $iblockId . '|' . $slug;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    if (!Loader::includeModule('iblock')) {
        return $cache[$key] = 0;
    }

    // 1) Основной путь — стабильный якорь XML_ID
    $section = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, 'XML_ID' => $slug, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID']
    )->Fetch();

    // 2) Фолбэк для баз без проставленных якорей — по символьному коду
    if (!$section) {
        $section = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'CODE' => $slug, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['ID']
        )->Fetch();
    }

    return $cache[$key] = $section ? (int)$section['ID'] : 0;
}

/**
 * Slug текущего лендинга раздела каталога; '' — страница не лендинг
 * (главная, /policy.php и т.п.).
 */
function latitudoCurrentLandingSlug(): string
{
    global $APPLICATION;
    $slug = is_object($APPLICATION) ? trim((string)$APPLICATION->GetCurDir(), '/') : '';

    return isset(LATITUDO_CATALOG_LANDINGS[$slug]) ? $slug : '';
}

/**
 * Есть ли в разделе активные элементы.
 * INCLUDE_SUBSECTIONS = Y — так же, как считает bitrix:catalog.section
 * (в его .parameters.php это значение по умолчанию), иначе счётчик разойдётся
 * с тем, что реально выводит блок товаров.
 */
function latitudoCatalogSectionHasItems(int $sectionId, int $iblockId = LATITUDO_CATALOG_IBLOCK_ID): bool
{
    static $cache = [];
    if ($sectionId <= 0) {
        return false;
    }
    $key = $iblockId . '|' . $sectionId;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    if (!Loader::includeModule('iblock')) {
        return $cache[$key] = false;
    }

    $count = (int)CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID'           => $iblockId,
            'SECTION_ID'          => $sectionId,
            'INCLUDE_SUBSECTIONS' => 'Y',
            'ACTIVE'              => 'Y',
            'ACTIVE_DATE'         => 'Y',
            'CHECK_PERMISSIONS'   => 'N',
        ],
        []
    );

    return $cache[$key] = $count > 0;
}

/**
 * Показывать ли пункт меню «Цены» (якорь #catalog).
 * На лендинге раздела блок товаров скрывается, если элементов нет, —
 * вместе с ним прячем и пункт меню, иначе ссылка ведёт в никуда.
 * На главной и прочих страницах якорь #catalog есть всегда.
 */
function latitudoShowCatalogMenuItem(): bool
{
    $slug = latitudoCurrentLandingSlug();
    if ($slug === '') {
        return true;
    }

    return latitudoCatalogSectionHasItems(latitudoCatalogSectionId($slug));
}
