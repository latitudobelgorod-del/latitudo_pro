<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
if (!CModule::IncludeModule("iblock")) die("iblock not loaded");
header("Content-Type: text/plain; charset=utf-8");

$res = CIBlockElement::GetList([], ['IBLOCK_ID' => 6, 'ACTIVE' => 'Y'], false, false,
    ['ID', 'NAME', 'PROPERTY_SUBDOMAIN', 'PROPERTY_MANAGER_NAME', 'PROPERTY_MANAGER_POSITION', 'PROPERTY_MANAGER_PHOTO', 'PROPERTY_GALLERY']);

while ($el = $res->Fetch()) {
    echo "ID={$el['ID']} ({$el['PROPERTY_SUBDOMAIN_VALUE']}) {$el['NAME']}\n";
    echo "  MANAGER_NAME     = " . var_export($el['PROPERTY_MANAGER_NAME_VALUE'], true) . "\n";
    echo "  MANAGER_POSITION = " . var_export($el['PROPERTY_MANAGER_POSITION_VALUE'], true) . "\n";
    echo "  MANAGER_PHOTO    = " . var_export($el['PROPERTY_MANAGER_PHOTO_VALUE'], true) . "\n";
    echo "  GALLERY          = " . var_export($el['PROPERTY_GALLERY_VALUE'], true) . "\n\n";
}
