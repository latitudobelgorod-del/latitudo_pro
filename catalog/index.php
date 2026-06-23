<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
    return;
}

// Код раздела берём из URL: /terrasnaya-doska/ → terrasnaya-doska
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestPath = trim($requestPath, '/');
$segments = explode('/', $requestPath);
$sectionCode = $segments[0] ?? '';

// Загружаем данные раздела для hero и SECTION_ID
$arSection = null;
if ($sectionCode) {
    $rsSection = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => 3, 'CODE' => $sectionCode, 'ACTIVE' => 'Y'],
        false,
        ['ID', 'NAME', 'DESCRIPTION', 'PICTURE', 'DETAIL_PICTURE']
    );
    $arSection = $rsSection->GetNext(false, false);
}

if (!$arSection) {
    CHTTP::SetStatus("404 Not Found");
    include($_SERVER["DOCUMENT_ROOT"] . "/404.php");
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
    return;
}

$APPLICATION->SetTitle($arSection['NAME']);

// Hero-изображение раздела
$heroUrl = '';
$fileId = $arSection['DETAIL_PICTURE'] ?: $arSection['PICTURE'];
if ($fileId) {
    $arFile = CFile::GetFileArray($fileId);
    if ($arFile) $heroUrl = $arFile['SRC'];
}
?>

<section class="hero"<?= $heroUrl ? ' style="background-image:url(\''.htmlspecialcharsbx($heroUrl).'\')"' : '' ?>>
    <div class="container">
        <h1 class="hero__title"><?= htmlspecialcharsbx($arSection['NAME']) ?></h1>
        <?php if (!empty($arSection['DESCRIPTION'])): ?>
        <p class="hero__subtitle"><?= htmlspecialcharsbx(strip_tags($arSection['DESCRIPTION'])) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section products-section" id="catalog">
    <div class="container">
        <h2 class="section__title">Товары и цены</h2>
        <?php
        $APPLICATION->IncludeComponent(
            "bitrix:news.list",
            "latitudo_products",
            [
                "IBLOCK_ID"           => "3",
                "SECTION_ID"          => $arSection['ID'],
                "SECTION_FILTER_MODE" => "SECTIONS",
                "FIELD_CODE"          => ["NAME", "PREVIEW_TEXT", "PREVIEW_PICTURE"],
                "PROPERTY_CODE"       => ["GALLERY", "PRICE_CURRENT", "PRICE_OLD"],
                "ELEMENT_SORT_FIELD"  => "SORT",
                "ELEMENT_SORT_ORDER"  => "ASC",
                "PAGE_ELEMENT_COUNT"  => "100",
                "CACHE_TYPE"          => "A",
                "CACHE_TIME"          => "3600",
                "CACHE_GROUPS"        => "Y",
            ]
        );
        ?>
    </div>
</section>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
