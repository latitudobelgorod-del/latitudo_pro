<?php
/**
 * Миграция: переименование кодов свойств инфоблока «Магазины / Регионы» (ID=6)
 * под единую схему REGION_* (совпадает с плейсхолдерами #REGION_*#). Идемпотентна.
 *
 * Запуск:
 *   локально:  C:/OSPanel/modules/PHP-8.2/php.exe -d short_open_tag=On -f tools/rename-store-props.php
 *   на проде:  ssh regru-latitudo "cd www/latitudo.pro && php tools/rename-store-props.php"
 *
 * ВАЖНО: код, читающий эти свойства (region.php, шаблон контактов, footer.php),
 * уже переведён на новые коды — запускать сразу после git pull.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }
$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $docRoot;
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
require($docRoot . '/bitrix/modules/main/include/prolog_before.php');
if (!\Bitrix\Main\Loader::includeModule('iblock')) { exit("Модуль iblock не загружен\n"); }

const STORES_IBLOCK_ID = 6;

$renames = [
    'PHONE'            => 'REGION_PHONE',
    'ADDRESS'          => 'REGION_ADDRESS',
    'EMAIL'            => 'REGION_EMAIL',
    'ORGANIZATION'     => 'REGION_ORG',
    'ADDRESS_WAREHOUSE'=> 'REGION_WAREHOUSE',
    'WORK_HOURS'       => 'REGION_WORK_HOURS',
];

$obProp = new CIBlockProperty();
foreach ($renames as $old => $new) {
    if (CIBlockProperty::GetList([], ['IBLOCK_ID' => STORES_IBLOCK_ID, 'CODE' => $new])->Fetch()) {
        echo "· {$new} уже есть — пропуск.\n";
        continue;
    }
    $p = CIBlockProperty::GetList([], ['IBLOCK_ID' => STORES_IBLOCK_ID, 'CODE' => $old])->Fetch();
    if (!$p) {
        echo "! {$old} не найдено — пропуск.\n";
        continue;
    }
    echo $obProp->Update($p['ID'], ['CODE' => $new])
        ? "+ {$old} → {$new}\n"
        : "! {$old} → {$new}: {$obProp->LAST_ERROR}\n";
}
echo "\nГотово.\n";
