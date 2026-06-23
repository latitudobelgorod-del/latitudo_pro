<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
if (!CModule::IncludeModule("iblock")) die("iblock not loaded");

header("Content-Type: text/plain; charset=utf-8");

$host  = mb_strtolower($_SERVER['HTTP_HOST'] ?? '');
$label = explode('.', $host)[0] ?? '';
$codes = ['msk','belgorod','vrn','krd','rnd'];
$regionCode = in_array($label, $codes, true) ? $label : 'krd';

echo "HOST: $host\n";
echo "REGION CODE: $regionCode\n\n";

// Все элементы iblock 6
$res = CIBlockElement::GetList([], ['IBLOCK_ID' => 6, 'ACTIVE' => 'Y'], false, false,
    ['ID', 'NAME', 'PROPERTY_SUBDOMAIN', 'PROPERTY_ORGANIZATION', 'PROPERTY_ADDRESS',
     'PROPERTY_ADDRESS_WAREHOUSE', 'PROPERTY_PHONE', 'PROPERTY_EMAIL',
     'PROPERTY_WORK_HOURS', 'PROPERTY_MAP_EMBED']);
echo "=== Elements in iblock 6 ===\n";
$count = 0;
while ($el = $res->Fetch()) {
    $count++;
    echo "ID={$el['ID']} NAME={$el['NAME']}\n";
    echo "  SUBDOMAIN        = " . var_export($el['PROPERTY_SUBDOMAIN_VALUE'], true) . "\n";
    echo "  ORGANIZATION     = " . var_export($el['PROPERTY_ORGANIZATION_VALUE'], true) . "\n";
    echo "  ADDRESS          = " . var_export($el['PROPERTY_ADDRESS_VALUE'], true) . "\n";
    echo "  ADDRESS_WAREHOUSE= " . var_export($el['PROPERTY_ADDRESS_WAREHOUSE_VALUE'], true) . "\n";
    echo "  PHONE            = " . var_export($el['PROPERTY_PHONE_VALUE'], true) . "\n";
    echo "  EMAIL            = " . var_export($el['PROPERTY_EMAIL_VALUE'], true) . "\n";
    echo "  WORK_HOURS       = " . var_export($el['PROPERTY_WORK_HOURS_VALUE'], true) . "\n";
    echo "  MAP_EMBED        = " . var_export($el['PROPERTY_MAP_EMBED_VALUE'], true) . "\n";
    echo "\n";
}
echo "Total elements: $count\n\n";

// Проверяем, существуют ли нужные свойства
echo "=== Properties on iblock 6 ===\n";
$rp = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => 6]);
while ($p = $rp->Fetch()) {
    echo "  CODE={$p['CODE']}  NAME={$p['NAME']}  TYPE={$p['PROPERTY_TYPE']}\n";
}

// Тест фильтра как в компоненте
echo "\n=== Filter test: PROPERTY_SUBDOMAIN = '$regionCode' ===\n";
$res2 = CIBlockElement::GetList([], ['IBLOCK_ID' => 6, 'ACTIVE' => 'Y', 'PROPERTY_SUBDOMAIN' => $regionCode],
    false, ['nTopCount' => 1], ['ID', 'NAME']);
$found = $res2->Fetch();
echo $found ? "FOUND: ID={$found['ID']} {$found['NAME']}\n" : "NOT FOUND — filter returns nothing\n";
