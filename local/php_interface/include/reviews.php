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
function latitudoSectionShowsReviews(string $sectionCode, int $catalogIblockId = 3): bool
{
    if (!Loader::includeModule('iblock')) {
        return false;
    }
    $res = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $catalogIblockId, 'CODE' => $sectionCode, 'ACTIVE' => 'Y'],
        false,
        ['ID', 'UF_SHOW_REVIEWS']
    );
    $section = $res->GetNext(false, false);
    if (!$section) {
        return true; // раздела нет — не повод прятать блок
    }

    return (string)($section['UF_SHOW_REVIEWS'] ?? '') !== '0';
}

/** Отзывы на лендинге раздела — с учётом галочки. Одна строка на странице. */
function latitudoShowReviewsForSection(string $sectionCode): void
{
    if (latitudoSectionShowsReviews($sectionCode)) {
        latitudoShowReviews();
    }
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

    $store = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;

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
            "SET_TITLE"                 => "N",
            "ADD_SECTIONS_CHAIN"        => "N",
            "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
            "PARENT_SECTION"            => "",
            "CHECK_DATES"               => "Y",
            "ACTIVE_DATE_FORMAT"        => "j F Y",
            // Шапка рейтинга — из текущего региона. Пустые значения = шапку не показываем.
            "YANDEX_RATING"             => $store['YANDEX_RATING'] ?? '',
            "YANDEX_RATING_COUNT"       => (int)($store['YANDEX_RATING_COUNT'] ?? 0),
            "YANDEX_REVIEWS_URL"        => $store['YANDEX_REVIEWS_URL'] ?? '',
        ],
        false
    );
}
