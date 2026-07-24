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
 * Все активные разделы каталога верхнего уровня — для сквозных списков ссылок
 * (меню «Все продукты» в шапке, колонка «Продукция» в подвале).
 *
 * Возвращает ID, NAME (сырой, экранировать на месте вывода) и URL лендинга.
 * Порядок — как в админке: SORT, затем название.
 *
 * ВАЖНО: сюда попадают ВСЕ активные разделы, галочка UF_SHOW_ON_MAIN_PAGE тут ни при
 * чём — она управляет только блоком «Каталог продукции» на главной. Раздел, снятый
 * с главной, обязан остаться в шапке и подвале, иначе на его лендинг не попасть.
 */
function latitudoCatalogLandings(int $iblockId = LATITUDO_CATALOG_IBLOCK_ID): array
{
    static $cache = [];
    if (isset($cache[$iblockId])) {
        return $cache[$iblockId];
    }
    if (!Loader::includeModule('iblock')) {
        return $cache[$iblockId] = [];
    }

    $items = [];
    $rs = CIBlockSection::GetList(
        ['SORT' => 'ASC', 'NAME' => 'ASC'],
        [
            'IBLOCK_ID'         => $iblockId,
            'ACTIVE'            => 'Y',
            'GLOBAL_ACTIVE'     => 'Y',
            'DEPTH_LEVEL'       => 1,
            'CHECK_PERMISSIONS' => 'N',
        ],
        false,
        ['ID', 'NAME', 'CODE', 'SECTION_PAGE_URL']
    );
    while ($section = $rs->GetNext(false, false)) {
        $code = trim((string)$section['CODE']);
        $items[] = [
            'ID'   => (int)$section['ID'],
            'NAME' => (string)$section['NAME'],
            // Адрес лендинга — по символьному коду (/terrasnaya-doska/), его же ждёт
            // диспетчер. Кода нет — берём штатный URL раздела.
            'URL'  => $code !== '' ? '/' . $code . '/' : (string)$section['SECTION_PAGE_URL'],
        ];
    }

    return $cache[$iblockId] = $items;
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
