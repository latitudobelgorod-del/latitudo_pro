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
const LATITUDO_DEFAULT_REGION   = 'krd'; // фоллбэк, когда городского поддомена нет
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
 * Данные текущего магазина (массив полей) или null. Кэш в рамках запроса.
 * Ключи: ID, CODE, CITY, CITY_IN, DESCRIPTION, PHONE, PHONE_HREF, ADDRESS, EMAIL,
 *        WORK_HOURS, REQUISITES, MAP_COORDS,
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
            'IBLOCK_ID'          => LATITUDO_STORES_IBLOCK_ID,
            'ACTIVE'             => 'Y',
            'PROPERTY_SUBDOMAIN' => $code,
            'CHECK_PERMISSIONS'  => 'N',
        ],
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME', 'PROPERTY_PHONE', 'PROPERTY_ADDRESS', 'PROPERTY_EMAIL', 'PROPERTY_WORK_HOURS', 'PROPERTY_REQUISITES', 'PROPERTY_MAP_COORDS']
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
    ];

    return $store;
}
?>
