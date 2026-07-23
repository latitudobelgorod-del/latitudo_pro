<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Блок «Отзывы» — обёртка над компонентом bitrix:news.list.
 * Один и тот же блок стоит на главной и на лендингах разделов, поэтому вызов вынесен в функцию:
 * на странице достаточно написать <? latitudoShowReviews(); ?>
 *
 * Источник отзывов — инфоблок с кодом REVIEWS (создаётся скриптом setup-reviews.php).
 * ID инфоблока НЕ хардкодим: на локальной базе и на сервере он может отличаться,
 * ищем по символьному коду.
 *
 * Шапка блока (5,0 / 91 оценка / «Читать все отзывы») берётся из инфоблока
 * «Магазины / Регионы» — у каждого поддомена свой рейтинг Яндекс.Карт (см. region.php).
 */

use Bitrix\Main\Loader;

const LATITUDO_REVIEWS_IBLOCK_CODE = 'REVIEWS';
const LATITUDO_REVIEWS_IBLOCK_TYPE = 'latitudo_content';

/** ID инфоблока «Отзывы» по его коду, либо 0. Кэш в рамках запроса. */
function latitudoReviewsIblockId(): int
{
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    if (!Loader::includeModule('iblock')) {
        return $id = 0;
    }
    $res = CIBlock::GetList([], [
        'TYPE'              => LATITUDO_REVIEWS_IBLOCK_TYPE,
        'CODE'              => LATITUDO_REVIEWS_IBLOCK_CODE,
        'ACTIVE'            => 'Y',
        'CHECK_PERMISSIONS' => 'N',
    ])->Fetch();

    return $id = $res ? (int)$res['ID'] : 0;
}

/** Склонение слова «оценка»: 1 оценка, 2 оценки, 91 оценка, 5 оценок. */
function latitudoPluralRatings(int $n): string
{
    $mod100 = $n % 100;
    $mod10  = $n % 10;
    if ($mod100 >= 11 && $mod100 <= 14) return 'оценок';
    if ($mod10 === 1)                   return 'оценка';
    if ($mod10 >= 2 && $mod10 <= 4)     return 'оценки';
    return 'оценок';
}

/**
 * Показывать ли отзывы на лендинге раздела каталога.
 * Управляется галочкой UF_SHOW_REVIEWS у раздела в админке.
 * Галочка не тронута (пустое значение) = «да», как договаривались.
 */
function latitudoSectionShowsReviews(string $sectionSlug, int $catalogIblockId = LATITUDO_CATALOG_IBLOCK_ID): bool
{
    if (!Loader::includeModule('iblock')) {
        return false;
    }
    // Раздел ищем по стабильному якорю, а не по символьному коду: код меняется
    // при переименовании раздела в админке (см. include/catalog-sections.php).
    $sectionId = latitudoCatalogSectionId($sectionSlug, $catalogIblockId);
    if (!$sectionId) {
        return true; // раздела нет — не повод прятать блок
    }
    $res = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $catalogIblockId, 'ID' => $sectionId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID', 'UF_SHOW_REVIEWS']
    );
    $section = $res->GetNext(false, false);
    if (!$section) {
        return true;
    }

    return (string)($section['UF_SHOW_REVIEWS'] ?? '') !== '0';
}

/** Отзывы на лендинге раздела — с учётом галочки. Одна строка на странице. */
function latitudoShowReviewsForSection(string $sectionSlug): void
{
    if (latitudoSectionShowsReviews($sectionSlug)) {
        latitudoShowReviews();
    }
}

/**
 * Шапка блока «Отзывы» для текущего региона: ['badgeSrc' => …, 'url' => …].
 *
 * Данные ведёт контент-менеджер в РАЗДЕЛАХ инфоблока «Отзывы» (по разделу на регион):
 *   UF_REGION         — привязка к элементу «Магазины/Регионы» (= latitudoCurrentStore()['ID']);
 *   UF_REGION_RAITING — HTML-вставка официального бейджа рейтинга Яндекс.Карт (iframe);
 *   UF_LINK_YANDEX    — ссылка на отзывы в Яндекс.Картах («Читать все отзывы»).
 *
 * БЕЗОПАСНОСТЬ. UF_REGION_RAITING — произвольный HTML из админки. Наружу его не отдаём:
 * достаём из src только org-id (цифры) и пересобираем iframe по фиксированному шаблону —
 * так чужой скрипт/разметка из поля на страницу не попадут. Ссылку пропускаем только https.
 *
 * Поля разделов есть не в каждой базе (на локалке структура могла отстать) — если поля
 * UF_REGION нет, тихо возвращаем пусто: блок отрисуется без шапки, страница цела.
 */
function latitudoReviewsRegionHeader(int $iblockId): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $empty = ['badgeSrc' => '', 'url' => '', 'sectionId' => 0];

    if (!$iblockId || !Loader::includeModule('iblock')) {
        return $cache = $empty;
    }
    $store = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
    if (!$store) {
        return $cache = $empty; // регион не определён — чей рейтинг показывать, неясно
    }
    // Поля UF_REGION может не быть в этой базе — фильтр по несуществующему UF роняет запрос.
    $ufEntity = 'IBLOCK_' . $iblockId . '_SECTION';
    $hasRegionUF = (new CUserTypeEntity())
        ->GetList([], ['ENTITY_ID' => $ufEntity, 'FIELD_NAME' => 'UF_REGION'])->Fetch();
    if (!$hasRegionUF) {
        return $cache = $empty;
    }

    $sec = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=UF_REGION' => $store['ID'], 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID', 'UF_REGION_RAITING', 'UF_LINK_YANDEX']
    )->GetNext(false, false);
    if (!$sec) {
        return $cache = $empty;
    }

    // Из HTML-бейджа берём только org-id (цифры) и собираем безопасный src виджета.
    $badgeSrc = '';
    if (preg_match('~rating-badge/(\d+)~', (string)($sec['UF_REGION_RAITING'] ?? ''), $m)) {
        $badgeSrc = 'https://yandex.ru/sprav/widget/rating-badge/' . $m[1] . '?type=rating';
    }
    $url = trim((string)($sec['UF_LINK_YANDEX'] ?? ''));
    if ($url !== '' && !preg_match('~^https://~i', $url)) {
        $url = '';
    }

    return $cache = ['badgeSrc' => $badgeSrc, 'url' => $url, 'sectionId' => (int)$sec['ID']];
}

/**
 * Выводит секцию «Отзывы» целиком (заголовок, шапка рейтинга, карусель карточек).
 * Если инфоблок не найден — молча ничего не выводит, страница не ломается.
 */
function latitudoShowReviews(): void
{
    global $APPLICATION;

    $iblockId = latitudoReviewsIblockId();
    if (!$iblockId) {
        return;
    }

    $store  = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
    $header = latitudoReviewsRegionHeader($iblockId);

    // Фильтр отзывов по региону через свойство CITY_BINDING («Город» — множественная
    // привязка к элементам «Магазины/Регионы»). Отзыв виден там, где выбран его регион.
    //
    // ВАЖНО: прямой фильтр компонента ['PROPERTY_CITY_BINDING' => $regionId] на этой базе
    // работает НЕВЕРНО (Битрикс возвращает не все элементы, хотя в хранилище значение есть).
    // Поэтому список ID собираем сами — ЧТЕНИЕМ свойства (оно отдаёт значения корректно) —
    // и передаём компоненту готовый ['ID' => ...]. Фильтр по ID у Битрикса надёжен.
    $regionId  = (int)($store['ID'] ?? 0);
    $reviewIds = [];
    if ($regionId) {
        $rs = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'PROPERTY_CITY_BINDING']
        );
        $seen = [];
        while ($row = $rs->Fetch()) {
            if ((int)($row['PROPERTY_CITY_BINDING_VALUE'] ?? 0) === $regionId) {
                $seen[(int)$row['ID']] = true;
            }
        }
        $reviewIds = array_keys($seen);
    }
    // ['ID' => [0]] — заведомо пустая выборка, если у региона нет отзывов (блок скроется).
    $GLOBALS['arReviewsFilter'] = $regionId ? ['ID' => ($reviewIds ?: [0])] : [];

    $APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "latitudo_reviews",
        [
            "IBLOCK_TYPE"               => LATITUDO_REVIEWS_IBLOCK_TYPE,
            "IBLOCK_ID"                 => (string)$iblockId,
            "NEWS_COUNT"                => "12", // по ТЗ карточек 4–12
            "SORT_BY1"                  => "SORT",
            "SORT_ORDER1"               => "ASC",
            "SORT_BY2"                  => "ACTIVE_FROM",
            "SORT_ORDER2"               => "DESC",
            "FIELD_CODE"                => ["PREVIEW_TEXT", "DATE_ACTIVE_FROM", ""],
            "PROPERTY_CODE"             => ["DATE", "RATING", "PHOTOS", ""],
            "DETAIL_URL"                => "",
            "AJAX_MODE"                 => "N",
            "DISPLAY_TOP_PAGER"         => "N",
            "DISPLAY_BOTTOM_PAGER"      => "N",
            "CACHE_TYPE"                => "A",
            "CACHE_TIME"                => "36000",
            "CACHE_GROUPS"              => "Y",
            "CACHE_FILTER"              => "Y", // учитывать фильтр региона в ключе кэша
            "SET_TITLE"                 => "N",
            "ADD_SECTIONS_CHAIN"        => "N",
            "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
            "PARENT_SECTION"            => "",
            // Регион фильтруем через глобальный массив (штатный механизм news.list; фильтр
            // входит в ключ кэша, поэтому кэш разводится по регионам). Пустой регион = все.
            "FILTER_NAME"               => $regionId ? "arReviewsFilter" : "",
            "CHECK_DATES"               => "Y",
            "ACTIVE_DATE_FORMAT"        => "j F Y",
            // Шапка рейтинга — из раздела инфоблока «Отзывы», привязанного к текущему
            // региону (UF_REGION). Бейдж — официальный виджет Яндекс.Карт (UF_REGION_RAITING),
            // ссылка «Читать все отзывы» — UF_LINK_YANDEX. Разные значения по регионам сами
            // разводят кэш компонента (arParams входит в ключ кэша).
            "YANDEX_BADGE_SRC"          => $header['badgeSrc'],
            "YANDEX_REVIEWS_URL"        => $header['url'] !== '' ? $header['url'] : ($store['YANDEX_REVIEWS_URL'] ?? ''),
            // Запасной числовой рейтинг из инфоблока «Магазины» (если бейдж не задан).
            "YANDEX_RATING"             => $store['YANDEX_RATING'] ?? '',
            "YANDEX_RATING_COUNT"       => (int)($store['YANDEX_RATING_COUNT'] ?? 0),
        ],
        false
    );
}
