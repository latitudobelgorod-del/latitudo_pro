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
 *   — привязка к разделам пуста ИЛИ содержит раздел текущего лендинга;
 *   — акция лежит в папке этого лендинга ЛИБО вне папок с «краевой» сортировкой (ниже).
 * Нет подходящих акций — блок просто не выводится, страница не ломается.
 *
 * ПОРЯДОК И ПАПКИ. Свои акции лендинга контент-редакторы складывают в одноимённую папку
 * (раздел) инфоблока «Акции» — там у каждого лендинга своя сотня сортировки (заборы 2xx,
 * фасады 7xx, террасная доска 8xx). Сквозные акции лежат вне папок, и место в ряду им
 * задаёт сортировка: до 100 включительно — идут ПЕРЕД акциями папки, от 1000 — встают
 * третьей карточкой ряда, после чего акции папки продолжаются. Акции из ЧУЖИХ папок
 * ряд замыкают — их всё равно видно, но после «своих».
 * Итоговый ряд: [сквозные ≤100] → [папка] → [сквозные ≥1000] → [папка дальше] → [чужие папки].
 * Сквозная акция без папки с сортировкой между этими границами не попадёт никуда —
 * это защита от «потерянных» акций: у элемента либо папка, либо краевая сортировка.
 * Сам порядок групп раскладывает шаблон компонента (одним запросом это не выражается).
 */

use Bitrix\Main\Loader;

const LATITUDO_PROMOS_IBLOCK_CODE = 'PROMOS';
const LATITUDO_PROMOS_IBLOCK_TYPE = 'latitudo_content';

/** Граница сортировки для сквозных акций, идущих ПЕРЕД акциями папки (включительно). */
const LATITUDO_PROMOS_SORT_HEAD = 100;
/** Граница сортировки для сквозных акций, идущих ПОСЛЕ акций папки (включительно). */
const LATITUDO_PROMOS_SORT_TAIL = 1000;
/** Место таких акций в ряду (1 = первая карточка). Ряд короче — встают в конец. */
const LATITUDO_PROMOS_TAIL_POSITION = 3;

/**
 * Условия акции → HTML для попапа.
 *
 * ЗАЧЕМ. Контент-редакторы набирают условия построчно: абзацы и пункты списка с «•».
 * В базе это плоский текст с переносами \n и БЕЗ единого тега (проверено на всех
 * элементах инфоблока). Раньше шаблон выводил его как есть — переносы в HTML
 * схлопываются в пробелы, и попап показывал простыню на семь тысяч знаков.
 *
 * ПОЧЕМУ НЕ СМОТРИМ НА DETAIL_TEXT_TYPE как на истину. Тип поля выставлен вразнобой:
 * у «Бесплатная доставка по Воронежской области» (ID 454) стоит html, у трёх соседних
 * акций с точно таким же плоским текстом — text. Полагаться на флаг нельзя, поэтому
 * решение принимаем по СОДЕРЖИМОМУ: есть блочная разметка — доверяем ей и не трогаем;
 * нет — строим структуру сами. Тип нужен только для экранирования (см. ниже).
 *
 * Чистая функция, от Битрикса зависит только htmlspecialcharsbx.
 */
function latitudoPromoTermsHtml(string $text, string $type = ''): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    // Редактор действительно набрал разметку — она главнее наших догадок.
    if (preg_match('~<(?:p|br|ul|ol|li|div|h[1-6]|table)\b~i', $text)) {
        return $text;
    }

    // Экранируем только текстовое поле. У html-поля сущности (&mdash;, &nbsp;) уже
    // готовые, повторное экранирование превратило бы их в видимый «&amp;mdash;».
    $isHtml = strtolower($type) === 'html';

    // Маркеры пунктов: только «•», «·», «▪» и дефис с пробелом. Тире (– —) намеренно
    // НЕ маркер: в этих текстах оно встречается внутри фраз («Срок доставки — от 2
    // рабочих дней»), и строка, начавшаяся с тире, — обычная проза, а не пункт.
    $marker = '~^(?:[•·▪]|-\s)\s*~u';

    $html = '';
    $list = [];
    $flush = static function () use (&$list, &$html): void {
        if ($list) {
            $html .= '<ul><li>' . implode('</li><li>', $list) . '</li></ul>';
            $list = [];
        }
    };

    foreach (preg_split('~\R~u', $text) as $line) {
        $line = trim($line);
        if ($line === '') {
            $flush(); // пустая строка закрывает список, но своего абзаца не рождает
            continue;
        }
        $isItem = (bool)preg_match($marker, $line);
        $line   = preg_replace($marker, '', $line);
        $safe   = $isHtml ? $line : htmlspecialcharsbx($line);

        if ($isItem) {
            $list[] = $safe;
        } else {
            $flush();
            $html .= '<p>' . $safe . '</p>';
        }
    }
    $flush();

    return $html;
}

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

/**
 * Папка (раздел) инфоблока «Акции», в которую сложены акции этого лендинга. 0 — папки нет
 * (тогда на лендинге видны только сквозные акции).
 *
 * Папки заводят руками в админке, поэтому ищем по трём якорям подряд:
 *   1) XML_ID = slug — стабильный якорь, его проставляет tools/setup-promos.php;
 *   2) символьный код = slug — если папку завели с кодом;
 *   3) название папки = название раздела каталога — как их завели сейчас («Заборы»
 *      в «Акциях» ↔ «Заборы» в «Каталоге»). Хрупко к переименованию, поэтому и нужен XML_ID.
 */
function latitudoPromosFolderId(string $sectionSlug): int
{
    static $cache = [];
    if (isset($cache[$sectionSlug])) {
        return $cache[$sectionSlug];
    }

    $iblockId = latitudoPromosIblockId();
    if (!$iblockId || !preg_match('~^[a-z0-9_-]+$~', $sectionSlug)) {
        return $cache[$sectionSlug] = 0;
    }

    $anchors = [['XML_ID' => $sectionSlug], ['CODE' => $sectionSlug]];
    $section = function_exists('latitudoCatalogSectionBySlug') ? latitudoCatalogSectionBySlug($sectionSlug) : null;
    if ($section) {
        $anchors[] = ['=NAME' => $section['NAME']];
    }

    foreach ($anchors as $anchor) {
        $folder = CIBlockSection::GetList(
            [],
            $anchor + ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['ID']
        )->Fetch();
        if ($folder) {
            return $cache[$sectionSlug] = (int)$folder['ID'];
        }
    }

    return $cache[$sectionSlug] = 0;
}

/** Акции на лендинге раздела каталога. Одна строка на странице. */
function latitudoShowPromosForSection(string $sectionSlug): void
{
    latitudoShowPromos($sectionSlug);
}

/**
 * Выводит секцию «Акции месяца» (заголовок + карусель баннеров).
 * $sectionSlug — slug лендинга (= имя папки), на котором стоит блок; раздел ищется
 * по стабильному якорю, а не по символьному коду (см. include/catalog-sections.php);
 * null — без фильтра по разделу (на случай сквозного использования).
 */
function latitudoShowPromos(?string $sectionSlug = null): void
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
    $folderId = 0;
    if ($sectionSlug !== null) {
        $sectionId = latitudoCatalogSectionId($sectionSlug);
        $folderId  = latitudoPromosFolderId($sectionSlug);
        $filter[]  = ['LOGIC' => 'OR', ['PROPERTY_SECTIONS' => false], ['PROPERTY_SECTIONS' => $sectionId]];

        // Отсекаем только «потерянные» акции — те, что лежат вне папок и не попали ни в
        // одну краевую группу сортировки (см. шапку файла). Всё остальное показываем.
        // Фильтруем по IBLOCK_SECTION_ID (папка элемента), а НЕ по SECTION_ID: в привязках
        // разделов у этих элементов лежат ещё и разделы «Каталога» из свойства SECTIONS,
        // и фильтр по SECTION_ID зацепил бы их.
        //
        // ID папки лендинга держим ВНУТРИ фильтра осознанно: news.list подмешивает фильтр
        // в ключ кэша, и без него страницы разных лендингов получили бы общий кэш, хотя
        // порядок акций у них разный.
        $groups = [
            ['IBLOCK_SECTION_ID' => false, '<=SORT' => LATITUDO_PROMOS_SORT_HEAD],
            ['IBLOCK_SECTION_ID' => false, '>=SORT' => LATITUDO_PROMOS_SORT_TAIL],
            ['!IBLOCK_SECTION_ID' => false],   // всё, что лежит в любой папке
        ];
        if ($folderId) {
            $groups[] = ['IBLOCK_SECTION_ID' => $folderId];
        }
        $filter[] = array_merge(['LOGIC' => 'OR'], $groups);
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
            "NEWS_COUNT"                => "50",
            // Шаблону — чтобы отличить «свою» папку лендинга от чужих при раскладке ряда
            "PROMOS_FOLDER_ID"          => $folderId,
            "SORT_BY1"                  => "SORT",
            "SORT_ORDER1"               => "ASC",
            "SORT_BY2"                  => "ID",
            "SORT_ORDER2"               => "DESC",
            "FILTER_NAME"               => "latitudoPromosFilter",
            // SORT — шаблону, он раскладывает акции по группам (см. шапку файла)
            // DETAIL_TEXT_TYPE запрашиваем явно: шаблон решает по нему, экранировать
            // текст условий или нет (см. latitudoPromoTermsHtml).
            "FIELD_CODE"                => ["PREVIEW_PICTURE", "DETAIL_TEXT", "DETAIL_TEXT_TYPE", "SORT", ""],
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
