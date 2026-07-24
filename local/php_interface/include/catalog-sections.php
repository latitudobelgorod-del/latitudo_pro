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

/**
 * Slug текущего лендинга раздела каталога; '' — страница не лендинг
 * (главная, /policy.php и т.п.).
 *
 * GetCurDir() отдаёт адрес из строки браузера, а не путь подключённого файла, —
 * поэтому работает и под urlrewrite, когда страницу рисует общий диспетчер.
 */
function latitudoCurrentLandingSlug(): string
{
    global $APPLICATION;
    $slug = is_object($APPLICATION) ? trim((string)$APPLICATION->GetCurDir(), '/') : '';

    return latitudoCatalogSectionBySlug($slug) ? $slug : '';
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
