<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Разделы каталога: связь «URL лендинга → раздел инфоблока».
 *
 * ЗАЧЕМ. Раньше страницы лендингов ссылались на раздел по символьному коду
 * ("SECTION_CODE" => "izdeliya-dpk"). При переименовании раздела в админке Битрикс
 * перегенерирует символьный код из нового названия — код в файлах устаревает молча,
 * и страница отдаёт «Раздел не найден» (так сломался /fasady/ 2026-07-18).
 *
 * КАК ТЕПЕРЬ. Якорь — XML_ID раздела: админка его не трогает при переименовании.
 * XML_ID = slug лендинга (/fasady/ → 'fasady'). Фолбэк — поиск по CODE: он ловит
 * и базы без проставленных якорей, и переименованный раздел, на который каталог
 * главной уже ссылается новым кодом (SECTION_URL = "/#SECTION_CODE#/").
 * Проставить якоря: php tools/migrate-section-xmlid.php
 *
 * РОУТИНГ. Папок лендингов в структуре сайта НЕТ: все разделы (включая заведённые
 * в админке позже) обслуживает один диспетчер local/routes/catalog-landing.php —
 * на него правило в urlrewrite.php отправляет односегментные адреса вида /zabory/.
 * Поэтому список разделов больше не хранится в коде, он живёт в инфоблоке.
 */

use Bitrix\Main\Loader;

const LATITUDO_CATALOG_IBLOCK_ID = 3;

/**
 * Историческая карта «slug → символьные коды раздела». Рантайму не нужна: разделы
 * резолвятся из инфоблока. Осталась ради tools/migrate-section-xmlid.php — он по этим
 * кодам находит раздел и проставляет ему XML_ID. Первый код в списке — актуальный.
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
 * Раздел каталога по slug из URL; null — раздела с таким адресом нет.
 * Кроме идентификаторов отдаёт галочки блоков, которые есть не на каждом лендинге
 * (заводятся скриптом tools/setup-landing-blocks.php).
 *
 * Кэш в рамках запроса: страница дёргает резолвер несколько раз
 * (диспетчер, меню, блоки «Акции», «Отзывы», «Видео»).
 */
function latitudoCatalogSectionBySlug(string $slug, int $iblockId = LATITUDO_CATALOG_IBLOCK_ID): ?array
{
    static $cache = [];
    $key = $iblockId . '|' . $slug;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    // Slug приходит из адресной строки. В запрос пускаем только то, что вообще может
    // быть символьным кодом раздела, — заодно отсекает главную ('') и вложенные пути.
    if (!preg_match('~^[a-z0-9_-]+$~', $slug) || !Loader::includeModule('iblock')) {
        return $cache[$key] = null;
    }

    // UF-полей может не быть в этой базе (git pull прошёл, миграция ещё нет) —
    // выборку несуществующих UF Битрикс молча игнорирует, запрос не падает.
    $select = ['ID', 'NAME', 'CODE', 'XML_ID', 'UF_SHOW_ABOUT', 'UF_SHOW_HOW_WE_WORK'];

    // 1) Основной путь — стабильный якорь XML_ID
    $section = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, 'XML_ID' => $slug, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        $select
    )->Fetch();

    // 2) Фолбэк — по символьному коду
    if (!$section) {
        $section = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'CODE' => $slug, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            $select
        )->Fetch();
    }

    return $cache[$key] = $section ?: null;
}

/**
 * ID раздела каталога по slug лендинга. 0 — раздел не найден.
 */
function latitudoCatalogSectionId(string $slug, int $iblockId = LATITUDO_CATALOG_IBLOCK_ID): int
{
    $section = latitudoCatalogSectionBySlug($slug, $iblockId);

    return $section ? (int)$section['ID'] : 0;
}

/*
 * Здесь же раньше жили latitudoCurrentLandingSlug(), latitudoCatalogSectionHasItems()
 * и latitudoShowCatalogMenuItem(). Последняя решала на сервере, показывать ли пункт
 * меню «Цены», — из-за чего список пунктов получался разной длины на разных страницах
 * и меню разъезжалось: подписи приходили от одного списка, ссылки от другого.
 * Меню теперь всегда фиксированной длины (.top.menu.php), а лишний пункт скрывает JS
 * в footer.php по маркеру data-empty у якоря #catalog. Функции стали не нужны.
 */
