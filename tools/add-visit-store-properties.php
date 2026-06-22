<?php
// One-time migration: add gallery + manager properties to iblock 6 (Магазины/Регионы).
// Run once at http://latitudo.local/tools/add-visit-store-properties.php
// Delete or rename after use.

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

if (!CModule::IncludeModule("iblock")) {
    die("iblock module not loaded");
}

$iblockId = 6; // LATITUDO_STORES_IBLOCK_ID

$properties = [
    [
        "IBLOCK_ID"     => $iblockId,
        "NAME"          => "Галерея магазина",
        "ACTIVE"        => "Y",
        "SORT"          => 100,
        "CODE"          => "GALLERY",
        "PROPERTY_TYPE" => "F",
        "MULTIPLE"      => "Y",
        "IS_REQUIRED"   => "N",
        "FILE_TYPE"     => "jpg, jpeg, png, webp",
        "MULTIPLE_CNT"  => 5,
    ],
    [
        "IBLOCK_ID"     => $iblockId,
        "NAME"          => "Фото менеджера",
        "ACTIVE"        => "Y",
        "SORT"          => 110,
        "CODE"          => "MANAGER_PHOTO",
        "PROPERTY_TYPE" => "F",
        "MULTIPLE"      => "Y",
        "IS_REQUIRED"   => "N",
        "FILE_TYPE"     => "jpg, jpeg, png, webp",
        "MULTIPLE_CNT"  => 5,
    ],
    [
        "IBLOCK_ID"     => $iblockId,
        "NAME"          => "Имя менеджера",
        "ACTIVE"        => "Y",
        "SORT"          => 120,
        "CODE"          => "MANAGER_NAME",
        "PROPERTY_TYPE" => "S",
        "MULTIPLE"      => "Y",
        "IS_REQUIRED"   => "N",
    ],
    [
        "IBLOCK_ID"     => $iblockId,
        "NAME"          => "Должность менеджера",
        "ACTIVE"        => "Y",
        "SORT"          => 130,
        "CODE"          => "MANAGER_POSITION",
        "PROPERTY_TYPE" => "S",
        "MULTIPLE"      => "Y",
        "IS_REQUIRED"   => "N",
    ],
];

$prop   = new CIBlockProperty();
$added  = [];
$errors = [];

foreach ($properties as $arProp) {
    $rsExist = CIBlockProperty::GetList([], ["IBLOCK_ID" => $iblockId, "CODE" => $arProp["CODE"]]);
    if ($rsExist->Fetch()) {
        $added[] = $arProp["CODE"] . " — already exists, skipped";
        continue;
    }
    $propId = $prop->Add($arProp);
    if ($propId) {
        $added[] = $arProp["CODE"] . " — added (ID=$propId)";
    } else {
        $errors[] = $arProp["CODE"] . ": " . $prop->LAST_ERROR;
    }
}

header("Content-Type: text/plain; charset=utf-8");
echo "=== Latitudo: visit-store properties migration ===\n\n";
foreach ($added  as $msg) echo "[OK]    $msg\n";
foreach ($errors as $msg) echo "[ERROR] $msg\n";
echo "\nDone. Delete or rename this file after use.\n";
