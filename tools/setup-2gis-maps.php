<?php
/**
 * Карты в блоке «Контакты»: свойство «Схема проезда - 2ГИС» (TWO_GIS_CONSTR_MAP)
 * в инфоблоке «Магазины / Регионы» (ID=6) + заполнение его картами офисов.
 * Идемпотентна: повторный запуск ничего не ломает.
 *
 * Логика вывода (шаблон latitudo_contacts): TWO_GIS_CONSTR_MAP заполнено — карта 2ГИС,
 * пусто — карта Яндекса из MAP_EMBED («Embed-ссылка карты»). Чтобы вернуть филиалу
 * Яндекс, достаточно очистить поле 2ГИС в админке.
 *
 * Источник ссылок 2ГИС — таблица заказчика «2гис - конструктор карт.xls» от 2026-09-24
 * (строки «Офис …»; карты складов в той же таблице есть, но на сайт не ставятся).
 *
 * Ещё скрипт возвращает в MAP_EMBED карты Яндекса: первая версия (коммит от 2026-09-24)
 * по ошибке записала 2ГИС прямо в MAP_EMBED поверх них.
 *
 * Запуск:
 *   локально:  C:/OSPanel/modules/PHP-8.2/php.exe -d short_open_tag=On -f tools/setup-2gis-maps.php
 *   на проде:  ssh regru-latitudo "cd www/latitudo.pro && php tools/setup-2gis-maps.php"
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

// 1) Свойство
if (CIBlockProperty::GetList([], ['IBLOCK_ID' => STORES_IBLOCK_ID, 'CODE' => 'TWO_GIS_CONSTR_MAP'])->Fetch()) {
    echo "· Свойство TWO_GIS_CONSTR_MAP уже есть.\n";
} else {
    $obProp = new CIBlockProperty();
    $ok = $obProp->Add([
        'IBLOCK_ID'     => STORES_IBLOCK_ID,
        'NAME'          => 'Схема проезда - 2ГИС',
        'CODE'          => 'TWO_GIS_CONSTR_MAP',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE'      => 'N',
        'IS_REQUIRED'   => 'N',
        'ACTIVE'        => 'Y',
        'SORT'          => 175, // сразу за MAP_EMBED (170)
        'ROW_COUNT'     => 3,
        'COL_COUNT'     => 80,
        'HINT'          => 'Код <iframe> из конструктора карт 2ГИС или ссылка makemap.2gis.ru. Заполнено — выводится карта 2ГИС, пусто — карта Яндекса.',
    ]);
    echo $ok ? "+ Свойство TWO_GIS_CONSTR_MAP добавлено.\n" : "! Свойство TWO_GIS_CONSTR_MAP: {$obProp->LAST_ERROR}\n";
}

// 2) Карты. Код филиала (CODE элемента) => карта офиса 2ГИС
$twoGis = [
    // г. Москва, Киевское шоссе 22-й км, БП Румянцево, корп. Г
    'msk'      => 'https://makemap.2gis.ru/widget?data=eJw1UVFvmzAQ_i_eY1FnijEBqQ-T2dKkjoVppY1NfcjAcp2RGBmTjET57ztIdy8n3_d9953vLsi6RjnVLJXdK--M6lH264L82CmUoW9q6wenUIA6Zzvl_IxfUG1b6wD_VFMMAbg3vp0UeH2UvVgyzHel9ssc81d51I-PQGlUXzvTeWMPQCyevtxhvhlWgtWYJ6VuGMWcy56dNF47OQgWwvujnkj_kpdGsDtcPU21cJWvRt6wGPSAn3rIJZhKn7IU8x-lfsnlMWUnXOVyMLm0YhrmXO4mvPotfcNGzEl_04O_YJ858P6M4F9FMDxoeaWFgTfg74IRXH3VYrcJ8Xqjn4E_zyWWz-A3-WJc9XrzWgNfQ9ZdwdJTcfv8eXVo1F-Uhfh_XAOkb0sfp5V-bLyw5uCBX1s4jDls_XyQKLknEaULGsTxPY3ihCzeQG8alC3o9S1A-21X2N7cdntB7dajbKaSNInCB_oQpSQlAWonfOpGonQRhgmmMYkwAGdr9zAdha5wJdu239-Van_OVe8Gdf0HhPup6A',
    // г. Белгород, ул. Есенина, 9Б
    'belgorod' => 'https://makemap.2gis.ru/widget?data=eJw1j8lugzAQht9legyKzGYCUg6R22aRi0JUdVUOCCzqysHImLQJyrt3IOkc51_0fz1oUwojyqXQB2GNFC0knz3YUyMggUeR284IcKAxuhHGjnoPhVbaoH5XUIKHupVWDQmyOWZtumSEf-8qu7wn_Dk7VvM5WkrRFkY2VuoajdvVYkL4U7dO2Y7wt6yL2ZnwaFelDP9Zu06HcDvoL-R9ldmSxViaHUv2w9kiJJuHGfcXEyw-r-tS_ELikv-7OFBdgU7D3BvNVsvaor_QCC3r3I6wPp2GNIhd1wnJNAwD6kV7zMsSEhp5l70Dh7zZ6lZeh_egcgvJzRvSOPJ86hLqgBrksY16QeBTP5oFM1yn9QG3RdiJ_Fqp1y8h1Mf4taYTlz8FUXXL',
    // г. Воронеж, ул. Летчика Колесниченко, 67
    'vrn'      => 'https://makemap.2gis.ru/widget?data=eJw1UMtugzAQ_Bf3WBTZmIeNlENrVJLKRUArtWmVAwoWcQUYGUMfUf69BtS97czs7M5egNKV0KJKhGqF0VIMIPq4APPTCxCBB1GaUQvggF6rXmiz8JaWppl5-DjlQ5owyD-L2iQx5C_5VG-3dqASw0nL3kjVWWG2u7uF_Gncp-wZ8rCoU2Z7nJuKuZzFRU1ZyGWct5QheODFmTIPcp0PzGJm1k5Wm9zPM2Oa7CB_y6eKBZB7wz4-4IzRr2xdfFKN0nblzSmAtizyu-8q8Q0iBP_r6oB6DfyzxFnTZkp2ZnGwT5FdaZZnYLpBAfZdz_HRJvAI8ejRzssKRCQk16MD2rLP1CDXqBfQlAZEi5b61AsQ8YmPsQOamV7cApci6rsIY0QCe59Srb0utK72Z6ppXs9CNO8LavQorn_len_B',
    // г. Краснодар, ул. Гаражная, 107/1, офис 6
    'krd'      => 'https://makemap.2gis.ru/widget?data=eJw1kFFvgjAUhf_L3aPEXC12lMQHBpvToAFjsrDFBwINq0FKStEh8b-vyHYf73fuac_pQaqcK56vuDxzrQRvwP3qQXc1BxfeeKpbxcGCWsmaK_3gPWSylMrwp4yiGcO10OVwgZtL3OxWPoanfaFXAYaH-FIsl0aS8yZTotZCVkYYvXsTDLfteuenmATD0QuGJL5oHzEM9oV_LTCJ4razC9xEA_fMPtbaZwM_dddie_BIeHttjMcEk0PcGo91kDgh8Sbmwdu6yvkPuDP8n7sFxRi0G2L8pYykqLTRZ9KUIapUP0ogzpRRypht2Ysp0gVls6O5Fzm47Hl-P1pwTutINmIM1EOZanBHrU2YzQh1kBALygGPbpTNqUPRQeMKNynP5nOOMTXFyLL8-Oa8_HxstWr5_RfvXn43',
    // г. Ростов-на-Дону, ул. Ларина, 45с2 (этаж 2)
    'rnd'      => 'https://makemap.2gis.ru/widget?data=eJw1UF1PgzAU_S_1UbIUBqsl2cNSFNG6rGTGTLMHQhvsApSUwkSy_25h87615-Pec0agNBda8FioShgtRQvCrxGYoREgBE8iM50WwAGNVo3QZsZHkKtSaYvf5Stox-JGmnJSwJeetduYQHpKCxNHkO5ZX6zXlsJFm2vZGKlqS9w9b-4hfeuSLSkgRWnBJxFirVyynpMz5eR9evc8jiiJ0gITRGXEKkzO8BCxbkswpJs2iZgLD49DQk7pafaImJGb4XV_7vPInxb_JjUXPyB04f9cHFBcAw9TnFvanZK1sfxc2VJknZm5jCVeINdDjo8WXrDCKDhateQgfED-5eiAKmt2qpXXWCMoMwPCGzdwfS8IsId9B5QTPHsF2MU-9lfeA3btcUpV9jRkTW09qiw_voUoP-dfoztx-QNlV4Cz',
];

// Прежние карты Яндекс-конструктора — возвращаются в MAP_EMBED
$yandex = [
    'msk'      => 'https://yandex.ru/map-widget/v1/?um=constructor%3A3119daf179c088dc4bbdc185a0e2fe3b5ba061974b2be6436b5216ab54c45052&source=constructor',
    'belgorod' => 'https://yandex.ru/map-widget/v1/?um=constructor%3Ae922aaaa00038ed52a2bebc254b7389886f6b95e5db0d71c058840c0202c3122&source=constructor',
    'vrn'      => 'https://yandex.ru/map-widget/v1/?um=constructor%3A4731f536cb3b83386d96ab9dc297456038697c2414c336c83d7fe2b0a480f902&source=constructor',
    'krd'      => 'https://yandex.ru/map-widget/v1/?um=constructor%3A923ba97849219be2e7e4e39a4ac1048f7c9f2bec14473230a83724f93cc47456&source=constructor',
    'rnd'      => 'https://yandex.ru/map-widget/v1/?um=constructor%3Aa09f0b26f70ee25820277ec4ae52a111998603994005cb5789d21a5bc6ecd2a0&source=constructor',
];

$decode = static fn($v) => html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

foreach ($twoGis as $code => $src) {
    $el = CIBlockElement::GetList([], ['IBLOCK_ID' => STORES_IBLOCK_ID, '=CODE' => $code], false, false,
        ['ID', 'NAME', 'PROPERTY_MAP_EMBED', 'PROPERTY_TWO_GIS_CONSTR_MAP'])->Fetch();
    if (!$el) {
        echo "! Филиал {$code} не найден.\n";
        continue;
    }
    $set = [];
    // 2ГИС пишем, только если поле пустое — не затираем то, что поправили в админке
    if (trim($decode($el['PROPERTY_TWO_GIS_CONSTR_MAP_VALUE'])) === '') {
        $set['TWO_GIS_CONSTR_MAP'] = '<iframe src="' . $src . '" width="100%" height="600px" frameborder="0"></iframe>';
    }
    // Яндекс возвращаем, только если в MAP_EMBED стоит 2ГИС (след первой версии скрипта)
    if (stripos($decode($el['PROPERTY_MAP_EMBED_VALUE']), 'makemap.2gis.ru') !== false) {
        $set['MAP_EMBED'] = '<iframe src="' . htmlspecialchars($yandex[$code]) . '" width="100%" height="400" frameborder="0"></iframe>';
    }
    if (!$set) {
        echo "· {$el['NAME']}: всё уже на месте.\n";
        continue;
    }
    CIBlockElement::SetPropertyValuesEx($el['ID'], STORES_IBLOCK_ID, $set);
    echo "+ {$el['NAME']}: " . implode(', ', array_keys($set)) . " записано.\n";
}

echo "\nГотово.\n";
