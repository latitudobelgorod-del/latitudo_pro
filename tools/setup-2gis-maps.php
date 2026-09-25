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
 * Источник ссылок 2ГИС — таблица заказчика «2гис - конструктор карт.xls»: версия от 2026-09-24
 * (только офис) заменена версией от 2026-09-25 (офис + склад на одной карте, кроме Ростова).
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

// 2) Карты. Код филиала (CODE элемента) => карта 2ГИС.
// Источник — таблица заказчика «2гис - конструктор карт.xls» от 2026-09-25: на карте офис и склад.
// Центр и масштаб (mapPosition внутри data) ПЕРЕСЧИТАНЫ относительно таблицы: там центр стоял
// мимо точек, и на телефоне склад не попадал в кадр. Посчитано под самый тесный случай —
// мобильную карту ~360×296 px (320 минус наезд карточки); масштаб округлён вниз до целого.
// ⚠️ Виджет 2ГИС уже ~490 px не сжимается: рисует карту на ~490 и срезает правый край, то есть
// центр карты на телефоне оказывается у 245-го пикселя, а не посередине. Поэтому центр сдвинут
// на ~96 px к востоку — точки стоят левее середины, справа остаётся место под подписи.
// Проверено скриншотами на 360×296 и 700×520. data = base64url(zlib(JSON)).
$twoGis = [
    // г. Москва: офис (БП Румянцево) + склад (Домодедово, Купчинино)
    'msk'      => 'https://makemap.2gis.ru/widget?data=eNqlkk-P2jAQxb-LewSxA3FIQNpD1qHhj0kxm6pNqz1Q4ma9DRglBpYgvnvHoYsqtVIP9SXyvJk31u_lTHSZyVJmkdQbaUolKzL8eibmtJNkSN7LldmXkrTJrtQ7WZpGP5O1LnSJ-rt1H_CgbpQp7ARMPbGLWQqz0T2WM1mtS7UzSm9RXIyD1jwJ6Idw9MpZKKoTzWFai4OJHoCXojLsyGO2Av4iTMZOkI7F3rAW8I85pC_XPqyZx-A0V47YP4ZW963Xj4xR4N_FIWNdSEc58DtRxVEAXOcwC5f5gPmQ3qEeRcA_210tO9f4p6E4NG-ZV5M4iiHlOMt6kzDFu9UTsY-jB7zXccaSCcO3DOy7kqUyuA_3mjjik3DkYD_FL50lx8M6pBZCPdlm8pUMu_B2Lm2SX4GfLM5ftBdabQ32rzWGorYr04TheB3q9F3Xa7tup--4vus94bzKyLDfu7T_mdUtmdlYbAz7BtNQFMH939P5PY2DuSVwhNRHop6oMuYBR5pZxCA9Wjo1V2hoKXE7y8DSNc2di4M64lyCaUaB7duYKMRkls8DmxYmGLMBcGp9gKugN69v1P78x_6bo-94fUotx57rdXv-G0fXG1ye2mSz2i10pa44zqRYGZTcDoWBS502KWzZ8Ttd9EAXUmu9IUMfPZCiLopPz1IWX5qiKffy8hPmCAmc',
    // г. Белгород: офис (ул. Есенина, 9Б) + склад (Таврово-4, ул. Шоссейная 1А)
    'belgorod' => 'https://makemap.2gis.ru/widget?data=eNqtkEtvwjAQhP-LeyRCC7EDicSBOpSXG8Uc2tKKA00smjbgyHGggPjv3fA49tTuxdLMejXzHYk2qTIqHSq9VtZkqiTB25HYfaFIQB7U0lZGEYcURhfK2LN_JInOtUH_LvEAB32b2bz-AZOOLCI-h-mgh3KqysRkhc30Bs141G-AeKzGEZ-BeJGVzw8gOrNVxFGX5TgahiDK2n-C-UjalPsgPuU25TvB-wwmg65w-w08fBhvUvVNghbc5uSQ1aXEvo54bRDrbGNxP9FYNNss7bmg6zWZR_1Wy2HQZIx67c4C_2cpCSj1Ts5fAExHcm35O0xCmfd7v0CYYtuI72Eeyq3FV1Ab8d0K5rGs9nQFUyxth_dXCAyEi_vDeByO5Fe42yYh7f0LhA5lbruGQH3P9_0bBOay08Ih62UR6zK7BD-SfGnRQmBtYF3XIXkt4xXPZZ7rYxyt1ximhUewsM7z5w-l8tezak2lTj9Q_L4J',
    // г. Воронеж: офис (ул. Летчика Колесниченко, 67) + склад (ул. Дорожная, 86)
    'vrn'      => 'https://makemap.2gis.ru/widget?data=eNqtUMtOwzAQ_BdzJKqc90PqoTiQPkyUlAMU1ENJrNQoiSPHaSlR_51NSn8A2NvuzKxmpkdC5kyyPGKiYkpy1qLgrUfq1DAUoAe2U51kSEONFA2TasR7lIlSSMBvMgfDAK64KgcFXs3TSpF3vAzTcjadApSzNpO8UVzUQEjms1tMH7tFTJ4wdddFTGA3U5UTg5JwXfjEpRzEw30TpgdFjAFv4yihZGbFH8dDFlrD469FnbNPFOj4OmcNFZcgp8HmT4pE8FoBPxMQltc7NYY0_YluOKZm6xPHMQ3f24Ka5yiwsHfW_lLB0k2bmGzw6v738Suf6HhD13ufWJjKtCXh0CtwD8CN7gZNF0dzTF_SQ04cTK12EW7MhPjHpJj-Tz2OaTvuWJDlebZxLcj27PNWQ9WuSUTLL8F6VO4UQMC1Ldf3NFQO5_GLbukm2BGiAjMmPIFCRFk-7xkrX8erkh07fwOwAcVa',
    // г. Краснодар: офис (ул. Гаражная, 107/1) + склад (пос. Колосистый, Звездный пер., 15Д)
    'krd'      => 'https://makemap.2gis.ru/widget?data=eNqlkNFymzAQRf9FfTSTLhUmFjN5ICIldhQGpel0aCcP1ChUGWwxQthxPP73rnDbD2j0tvfuXu2eIzG2UVY1uTIb5axWA0l-HIk79Iok5LOq3WgVCUhvTa-sm_wjWZvOWPQ_rGPAh77TrvMTsLqUfcEruLu5QrlRw9rq3mmzRbO8TWcg7sdlwWuoMjkU-TUIKneOA4jsoeX7FqpSjoeohVXp_RR16Rxn3n857Nv7x5SKt5sBM2ZQPcoRM5ZZtRA0neGHb8tto15JEsLfdwpIez7u4Ff_c1lp9NZh_9ogAL2t3XQ4XVywOGYsCqL5BcTzmIVPOK8bklxSTHoPmLtbuXH8J6wy2aVX_wlHfMQ6vxY682HYv5tqEC8PbZNzqHZy_IJ9q2c5OB6CeJauQHiTnskdQw2zXZNn51keC56-Fg3_WnK2L9tpsfdDBGAR9RBD-omG9B_EODo9BWRT96UZ9PnwI-lqRxIPnNGQzQPSedmnMLZYxLiOMRtcJsQQBGa67tsvpbrvk-rsqE6_AdPK2lY',
    // г. Ростов-на-Дону: только офис (ул. Ларина, 45с2) — склада на карте нет
    'rnd'      => 'https://makemap.2gis.ru/widget?data=eJw1UNFOgzAU_Zfro2Qp26CBxIelnROHy0o0Zpo9ENpgl0JJKZtI9u8Wpvel6Tn3nnvPGUAbLozgG6ErYY0ULcSfA9i-ERDDo8htZwR40BjdCGMnfoBCK20cf1eEyJXjrbRqnEDPmDU7ckDb9YODuWgLIxsrde3I_dPqHqUvXbIjJUpxVvINcS9r5YKdObmknLyN_zPf0JTQrIwITiVlVUQu6EBZtyMRSldtQpmPDus-IafsNGlQZuWq375ezgVdjot_kpqLb4h99F9XD8qbyX608Odwr2VtXX-hXRCyzu0UwCKaYX-OvSWezYMwwsHRTUsOcYjR9ehBlTd73cqbrQFUbiEeexcBDkMP1AiPGiiKgrk7RuvKneI7EReHVur9Swj1MaHWdOL6Cz0QeQw',
];

// Ссылки первой партии (таблица от 2026-09-24, только офис). Если в поле стоит ровно такая —
// её написал этот скрипт, и её можно заменить новой; всё прочее считаем правкой из админки.
$twoGisPrev = [
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
    // 2ГИС пишем, если поле пустое или в нём прежняя ссылка этого же скрипта —
    // то, что поправили в админке руками, не затираем
    $curGis = trim($decode($el['PROPERTY_TWO_GIS_CONSTR_MAP_VALUE']));
    $curSrc = preg_match('~src="([^"]+)"~', $curGis, $m) ? $m[1] : $curGis;
    if ($curSrc !== $src && ($curGis === '' || $curSrc === ($twoGisPrev[$code] ?? null))) {
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
