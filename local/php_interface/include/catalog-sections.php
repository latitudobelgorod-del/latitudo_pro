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
