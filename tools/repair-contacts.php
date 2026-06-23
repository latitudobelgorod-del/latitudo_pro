<?php
// Восстанавливает SUBDOMAIN, PHONE, ADDRESS, EMAIL, WORK_HOURS для элементов инфоблока 6.
// Используется SetPropertyValuesEx() — обновляет только указанные свойства, не трогает остальные.
// Запустить ОДИН РАЗ: http://latituty.beget.tech/tools/repair-contacts.php
// После запуска — удалить файл.

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
if (!CModule::IncludeModule("iblock")) die("iblock not loaded");

header("Content-Type: text/plain; charset=utf-8");
echo "=== Latitudo: repair contacts properties ===\n\n";

const IBLOCK_ID = 6;

// ID элементов взяты из диагностики (ID=7..11)
$elements = [
    7  => [
        'subdomain'  => 'msk',
        'phone'      => '+7 (928) 443-07-77',
        'address'    => 'г. Москва, Киевское шоссе 22-й км, Бизнес-парк Румянцево, корп. Г, этаж 6, офис 635Г (подъезды 17 и 18)',
        'email'      => 'latitudo-msk@yandex.ru',
        'work_hours' => "Пн-пт: 9:00-18:00\nСб: 10:00-15:00",
    ],
    8  => [
        'subdomain'  => 'belgorod',
        'phone'      => '+7 (928) 443-07-77',
        'address'    => 'г. Белгород, (адрес офиса — уточнить)',
        'email'      => 'latitudo-belgorod@gmail.com',
        'work_hours' => "Пн-пт: 9:00-18:00\nСб: 10:00-15:00",
    ],
    9  => [
        'subdomain'  => 'vrn',
        'phone'      => '+7 (928) 443-07-77',
        'address'    => 'г. Воронеж, (адрес офиса — уточнить)',
        'email'      => 'latitudo-vrn@yandex.ru',
        'work_hours' => "Пн-пт: 9:00-18:00\nСб: 10:00-15:00",
    ],
    10 => [
        'subdomain'  => 'krd',
        'phone'      => '+7 (928) 443-07-77',
        'address'    => 'г. Краснодар, пос. Колосистый, Звёздный переулок, 15Д',
        'email'      => 'latitudo-krd@yandex.ru',
        'work_hours' => "Пн-пт: 9:00-18:00\nСб: 10:00-15:00",
    ],
    11 => [
        'subdomain'  => 'rnd',
        'phone'      => '+7 (928) 443-07-77',
        'address'    => 'г. Ростов-на-Дону, (адрес офиса — уточнить)',
        'email'      => 'latitudo-rnd@yandex.ru',
        'work_hours' => "Пн-пт: 9:00-18:00\nСб: 10:00-15:00",
    ],
];

foreach ($elements as $id => $data) {
    // SetPropertyValuesEx обновляет только указанные свойства, не трогает GALLERY, ORGANIZATION и т.д.
    $result = CIBlockElement::SetPropertyValuesEx($id, IBLOCK_ID, [
        'SUBDOMAIN'  => $data['subdomain'],
        'PHONE'      => $data['phone'],
        'ADDRESS'    => $data['address'],
        'EMAIL'      => $data['email'],
        'WORK_HOURS' => $data['work_hours'],
    ]);

    if ($result) {
        echo "[OK]    ID=$id ({$data['subdomain']}): SUBDOMAIN, PHONE, ADDRESS, EMAIL, WORK_HOURS восстановлены\n";
    } else {
        echo "[ERROR] ID=$id ({$data['subdomain']}): ошибка при обновлении\n";
    }
}

echo "\nГотово. Проверь сайт и удали этот файл.\n";
