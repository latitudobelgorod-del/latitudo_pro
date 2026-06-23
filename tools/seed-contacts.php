<?php
// Заполняет свойства ORGANIZATION, ADDRESS_WAREHOUSE, MAP_EMBED демо-данными
// для всех элементов инфоблока «Магазины / Регионы» (IBLOCK_ID=6).
// Запустить ОДИН РАЗ: http://latitudo.local/tools/seed-contacts.php
// После запуска — удалить или переименовать файл.

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

if (!CModule::IncludeModule("iblock")) {
    die("iblock module not loaded");
}

const IBLOCK_ID = 6;

// Демо-данные по поддомену
$demoData = [
    'krd' => [
        'ORGANIZATION'      => 'ООО «Латитудо-М» ОГРН 1217700295075',
        'ADDRESS_WAREHOUSE' => 'г. Краснодар, пос. Колосистый, Звёздный переулок, 15Д',
        'MAP_EMBED'         => 'https://yandex.ru/map-widget/v1/?ll=38.9965%2C45.1062&z=14&pt=38.9965,45.1062,pm2rdm',
    ],
    'msk' => [
        'ORGANIZATION'      => 'ООО «Латитудо-М» ОГРН 1217700295075',
        'ADDRESS_WAREHOUSE' => 'г. Москва, Киевское шоссе 22-й км, Бизнес-парк Румянцево, корп. Г, этаж 6, офис 635Г',
        'MAP_EMBED'         => 'https://yandex.ru/map-widget/v1/?ll=37.3872%2C55.6351&z=14&pt=37.3872,55.6351,pm2rdm',
    ],
    'belgorod' => [
        'ORGANIZATION'      => 'ООО «Латитудо-М» ОГРН 1217700295075',
        'ADDRESS_WAREHOUSE' => 'г. Белгород, (адрес склада — уточнить)',
        'MAP_EMBED'         => 'https://yandex.ru/map-widget/v1/?ll=36.5983%2C50.5977&z=13&pt=36.5983,50.5977,pm2rdm',
    ],
    'vrn' => [
        'ORGANIZATION'      => 'ООО «Латитудо-М» ОГРН 1217700295075',
        'ADDRESS_WAREHOUSE' => 'г. Воронеж, (адрес склада — уточнить)',
        'MAP_EMBED'         => 'https://yandex.ru/map-widget/v1/?ll=39.1843%2C51.6755&z=13&pt=39.1843,51.6755,pm2rdm',
    ],
    'rnd' => [
        'ORGANIZATION'      => 'ООО «Латитудо-М» ОГРН 1217700295075',
        'ADDRESS_WAREHOUSE' => 'г. Ростов-на-Дону, (адрес склада — уточнить)',
        'MAP_EMBED'         => 'https://yandex.ru/map-widget/v1/?ll=39.7015%2C47.2357&z=13&pt=39.7015,47.2357,pm2rdm',
    ],
];

header("Content-Type: text/plain; charset=utf-8");
echo "=== Latitudo: seed contacts data ===\n\n";

$res = CIBlockElement::GetList(
    [],
    ['IBLOCK_ID' => IBLOCK_ID, 'ACTIVE' => 'Y'],
    false,
    false,
    ['ID', 'NAME', 'PROPERTY_SUBDOMAIN']
);

$el = new CIBlockElement();

while ($item = $res->Fetch()) {
    $id      = (int)$item['ID'];
    $subdomain = (string)$item['PROPERTY_SUBDOMAIN_VALUE'];
    $name    = $item['NAME'];

    if (!isset($demoData[$subdomain])) {
        echo "[SKIP]  ID=$id, $name (нет данных для поддомена «$subdomain»)\n";
        continue;
    }

    $props = [];
    foreach ($demoData[$subdomain] as $code => $val) {
        $props[$code] = $val;
    }

    $el->Update($id, ['PROPERTY_VALUES' => $props]);

    if ($el->LAST_ERROR) {
        echo "[ERROR] ID=$id ($name / $subdomain): " . $el->LAST_ERROR . "\n";
    } else {
        echo "[OK]    ID=$id ($name / $subdomain): ORGANIZATION, ADDRESS_WAREHOUSE, MAP_EMBED — заполнены\n";
    }
}

echo "\nГотово. Удали файл tools/seed-contacts.php после использования.\n";
