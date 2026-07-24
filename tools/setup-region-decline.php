<?php
/**
 * Миграция: поля склонений названия города в инфоблоке «Магазины / Регионы» (ID=6).
 * Идемпотентна: повторный запуск ничего не ломает.
 *
 * Для чего: региональная подстановка #REGION_NAME_DECLINE_*# (см. include/region.php,
 * latitudoRegionVarsMap + обработчик OnEndBufferContent в init.php). Контент-менеджер
 * заполняет падежи по каждому городу, а на сайте они подставляются по текущему поддомену.
 *
 * Запуск:
 *   локально:  C:/OSPanel/modules/PHP-8.2/php.exe -d short_open_tag=On -f tools/setup-region-decline.php
 *   на проде:  ssh regru-latitudo "cd www/latitudo.pro && php tools/setup-region-decline.php"
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}
$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $docRoot;

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
require($docRoot . '/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    exit("Модуль iblock не загружен\n");
}

const STORES_IBLOCK_ID = 6; // Магазины / Регионы

$props = [
    ['CODE' => 'REGION_NAME_DECLINE_RP', 'NAME' => 'Название города в родительном падеже',   'SORT' => 700, 'HINT' => 'Например: Ростова-на-Дону (кого/чего)'],
    ['CODE' => 'REGION_NAME_DECLINE_PP', 'NAME' => 'Название города в предложном падеже',     'SORT' => 710, 'HINT' => 'Например: Ростове-на-Дону (о ком/о чём)'],
    ['CODE' => 'REGION_NAME_DECLINE_TP', 'NAME' => 'Название города в творительном падеже',   'SORT' => 720, 'HINT' => 'Например: Ростовом-на-Дону (кем/чем)'],
    ['CODE' => 'REGION_TAG_OBLAST',         'NAME' => 'Подпись — в городе и области',          'SORT' => 730, 'HINT' => 'Например: в Ростове-на-Дону и Ростовской области'],
    ['CODE' => 'REGION_TAG_OBLAST_DP',      'NAME' => 'Подпись — в городе и области (ДП)',     'SORT' => 740, 'HINT' => 'Дательный падеж'],
    ['CODE' => 'REGION_TAG_SEO_OBLAST_IP',  'NAME' => 'Подпись — город и область (SEO)',       'SORT' => 750, 'HINT' => 'Именительный: Ростов-на-Дону и Ростовская область'],
    ['CODE' => 'REGION_TAG_SEO_OBLAST_PP',  'NAME' => 'Подпись — городе и области (SEO)',       'SORT' => 760, 'HINT' => 'Предложный: Ростове-на-Дону и Ростовской области'],
];

$obProp = new CIBlockProperty();
foreach ($props as $p) {
    $has = CIBlockProperty::GetList([], ['IBLOCK_ID' => STORES_IBLOCK_ID, 'CODE' => $p['CODE']])->Fetch();
    if ($has) {
        echo "· Свойство {$p['CODE']} уже есть.\n";
        continue;
    }
    $ok = $obProp->Add([
        'IBLOCK_ID'     => STORES_IBLOCK_ID,
        'NAME'          => $p['NAME'],
        'CODE'          => $p['CODE'],
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE'      => 'N',
        'ACTIVE'        => 'Y',
        'SORT'          => $p['SORT'],
        'HINT'          => $p['HINT'],
    ]);
    echo $ok ? "+ Свойство {$p['CODE']} добавлено.\n" : "! Свойство {$p['CODE']}: {$obProp->LAST_ERROR}\n";
}

echo "\nГотово. Заполните падежи по каждому городу в разделе Контент → «Магазины / Регионы».\n";
