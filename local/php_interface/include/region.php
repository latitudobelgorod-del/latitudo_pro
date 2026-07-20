<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Регион (филиал) по поддомену. Источник — инфоблок «Магазины / Регионы» (IBLOCK_ID=6).
 * Контакты в шапке, подвале, блоке «Посетите магазин» и меню «Магазин в …» берутся отсюда.
 *
 * Поддомены: msk, belgorod, vrn, krd, rnd. На голом домене / локалке / beget
 * (без городского поддомена) используется регион по умолчанию.
 */

use Bitrix\Main\Loader;

const LATITUDO_STORES_IBLOCK_ID = 6;
const LATITUDO_DEFAULT_REGION   = 'msk'; // фоллбэк: голый latitudo.pro / www / локалка = Москва
const LATITUDO_REGION_CODES     = ['msk', 'belgorod', 'vrn', 'krd', 'rnd'];

/** Предложный падеж города для пункта меню «Магазин в …». */
function latitudoRegionPrepositional(string $code, string $cityName): string
{
    $map = [
        'msk'      => 'Москве',
        'belgorod' => 'Белгороде',
        'vrn'      => 'Воронеже',
        'krd'      => 'Краснодаре',
        'rnd'      => 'Ростове-на-Дону',
    ];
    return $map[$code] ?? $cityName;
}

/**
 * Зона бесплатной доставки для подзаголовка hero: «по <город> и <region>».
 * Меняется по поддомену (городу). Фоллбэк на голом домене — «по всей России».
 */
function latitudoRegionDeliveryZone(string $code): string
{
    $map = [
        'msk'      => 'по Москве и всей Московской области',
        'belgorod' => 'по Белгороду и всей Белгородской области',
        'vrn'      => 'по Воронежу и всей Воронежской области',
        'krd'      => 'по Краснодару и всему Краснодарскому краю',
        'rnd'      => 'по Ростову-на-Дону и всей Ростовской области',
    ];
    return $map[$code] ?? 'по всей России';
}

/** Код текущего региона по первому сегменту хоста; фоллбэк — регион по умолчанию. */
function latitudoCurrentRegionCode(): string
{
    static $code = null;
    if ($code !== null) {
        return $code;
    }

    $host  = isset($_SERVER['HTTP_HOST']) ? mb_strtolower($_SERVER['HTTP_HOST']) : '';
    $label = explode('.', $host)[0] ?? '';

    $code = in_array($label, LATITUDO_REGION_CODES, true) ? $label : LATITUDO_DEFAULT_REGION;
    return $code;
}

/**
 * Базовый домен без городского префикса. Нужен, чтобы переключатель городов
 * строил ссылки от ТЕКУЩЕГО хоста — одинаково на боевом (latitudo.pro)
 * и на демо-стенде (latituty.beget.tech), без жёстко зашитого домена.
 *   msk.latitudo.pro        → latitudo.pro
 *   latitudo.pro            → latitudo.pro
 *   msk.latituty.beget.tech → latituty.beget.tech
 */
function latitudoBaseHost(): string
{
    $host  = isset($_SERVER['HTTP_HOST']) ? mb_strtolower($_SERVER['HTTP_HOST']) : '';
    $host  = preg_replace('/:\d+$/', '', $host); // убрать :порт (локалка)
    $parts = explode('.', $host);

    // Отрезаем первое слово, только если это код города или www.
    if (count($parts) > 1) {
        $first = $parts[0];
        if (in_array($first, LATITUDO_REGION_CODES, true) || $first === 'www') {
            array_shift($parts);
        }
    }
    return implode('.', $parts);
}

/** Абсолютная ссылка на главную нужного города от текущего хоста. */
function latitudoCityUrl(string $code): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base   = latitudoBaseHost();
    return $base === '' ? '/' : $scheme . '://' . $code . '.' . $base . '/';
}

/**
 * Безопасный вывод текстового поля филиала (адрес, график работы, организация).
 *
 * Зачем: одни и те же поля приходят в шаблоны по ДВУМ разным путям, и экранированы они
 * по-разному. Компонент bitrix:news.list отдаёт значения свойств уже экранированными
 * (кавычка приходит как «&quot;»), а latitudoCurrentStore() читает их из базы сырыми.
 * Шаблон, экранируя ещё раз, во втором случае давал правильный результат, а в первом —
 * «ООО &quot;Латитудо-М&quot;» прямо на странице.
 *
 * Что делает функция:
 *   1) снимает уже имеющееся экранирование (оба входа приводятся к сырому тексту);
 *   2) экранирует РОВНО один раз — защита от XSS остаётся;
 *   3) возвращает на место переносы строк: и настоящий перевод строки из админки,
 *      и написанный руками тег <br>. Всё остальное HTML остаётся обезвреженным —
 *      это белый список, а не «разрешить любой HTML».
 *
 * Результат — готовая к выводу HTML-строка, повторно экранировать её НЕ нужно.
 */
function latitudoStoreText(?string $value): string
{
    $raw = trim((string)$value);
    if ($raw === '') {
        return '';
    }

    // Шаг 1 — к сырому виду. Сырой текст от такой распаковки не меняется,
    // а уже экранированный («&quot;», «&lt;br&gt;») возвращается к «"» и «<br>».
    $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Шаг 2 — единственное экранирование.
    $safe = htmlspecialcharsbx($raw);

    // Шаг 3 — белый список: только перенос строки.
    $safe = preg_replace('~&lt;br\s*/?&gt;~i', '<br>', $safe);

    return nl2br($safe, false);
}

/**
 * Данные текущего магазина (массив полей) или null. Кэш в рамках запроса.
 * Ключи: ID, CODE, CITY, CITY_IN, DESCRIPTION, PHONE, PHONE_HREF, ADDRESS, EMAIL,
 *        WORK_HOURS, REQUISITES, MAP_COORDS,
 *        YANDEX_RATING, YANDEX_RATING_COUNT, YANDEX_REVIEWS_URL,
 *        GALLERY ([]int fileId), MANAGER_PHOTOS ([]int), MANAGER_NAMES ([]string), MANAGER_POSITIONS ([]string).
 */
function latitudoCurrentStore(): ?array
{
    static $store = false;
    if ($store !== false) {
        return $store;
    }

    if (!Loader::includeModule('iblock')) {
        return $store = null;
    }

    $code = latitudoCurrentRegionCode();
    $res = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID'         => LATITUDO_STORES_IBLOCK_ID,
            'ACTIVE'            => 'Y',
            // Ищем по CODE элемента (= короткий поддомен msk/krd/...): он структурный
            // и стабильный. Свойство SUBDOMAIN админ может заполнить полным доменом
            // (msk.latitudo.pro) — по нему матчить нельзя, ломается выбор филиала.
            '=CODE'             => $code,
            'CHECK_PERMISSIONS' => 'N',
        ],
        false,
        ['nTopCount' => 1],
        [
            'ID', 'NAME', 'PROPERTY_PHONE', 'PROPERTY_ADDRESS', 'PROPERTY_EMAIL', 'PROPERTY_WORK_HOURS',
            'PROPERTY_REQUISITES', 'PROPERTY_MAP_COORDS',
            'PROPERTY_YANDEX_RATING', 'PROPERTY_YANDEX_RATING_COUNT', 'PROPERTY_YANDEX_REVIEWS_URL',
            'PROPERTY_TELEGRAM', 'PROPERTY_WHATSAPP', 'PROPERTY_MAX',
        ]
    );
    $el = $res->Fetch();
    if (!$el) {
        return $store = null;
    }

    $requisites = $el['PROPERTY_REQUISITES_VALUE'];
    if (is_array($requisites)) {
        $requisites = $requisites['TEXT'] ?? '';
    }

    $phone = (string)$el['PROPERTY_PHONE_VALUE'];

    $store = [
        'ID'          => (int)$el['ID'],
        'CODE'        => $code,
        'CITY'        => $el['NAME'],
        'CITY_IN'     => latitudoRegionPrepositional($code, $el['NAME']),
        'PHONE'       => $phone,
        'PHONE_HREF'  => 'tel:' . preg_replace('/[^\d+]/', '', $phone),
        'ADDRESS'     => (string)$el['PROPERTY_ADDRESS_VALUE'],
        'EMAIL'       => (string)$el['PROPERTY_EMAIL_VALUE'],
        'WORK_HOURS'  => (string)$el['PROPERTY_WORK_HOURS_VALUE'],
        'REQUISITES'  => (string)$requisites,
        'MAP_COORDS'  => (string)$el['PROPERTY_MAP_COORDS_VALUE'],
        // Рейтинг Яндекс.Карт — свой у каждого филиала, показывается в шапке блока «Отзывы».
        'YANDEX_RATING'       => (string)$el['PROPERTY_YANDEX_RATING_VALUE'],
        'YANDEX_RATING_COUNT' => (int)$el['PROPERTY_YANDEX_RATING_COUNT_VALUE'],
        'YANDEX_REVIEWS_URL'  => (string)$el['PROPERTY_YANDEX_REVIEWS_URL_VALUE'],
        // Мессенджеры филиала — для кнопки «Написать в мессенджер» (баннер «Есть вопросы?»)
        'TELEGRAM'            => (string)$el['PROPERTY_TELEGRAM_VALUE'],
        'WHATSAPP'            => (string)$el['PROPERTY_WHATSAPP_VALUE'],
        'MAX'                 => (string)$el['PROPERTY_MAX_VALUE'],
    ];

    return $store;
}
?>
