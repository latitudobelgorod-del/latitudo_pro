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

$iprop    = new \Bitrix\Iblock\InheritedProperty\SectionValues(3, (int)$arSection['ID']);
$ipropVals = $iprop->getValues();
$seoH1    = !empty($ipropVals['SECTION_PAGE_TITLE'])
    ? $ipropVals['SECTION_PAGE_TITLE']
    : $arSection['NAME'];

$APPLICATION->SetTitle($seoH1);

// Hero-изображение раздела
$heroUrl = '';
$fileId = $arSection['DETAIL_PICTURE'] ?: $arSection['PICTURE'];
if ($fileId) {
    $arFile = CFile::GetFileArray($fileId);
    if ($arFile) $heroUrl = $arFile['SRC'];
}

$heroStore  = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
$heroCityIn = ($heroStore && !empty($heroStore['CITY_IN'])) ? $heroStore['CITY_IN'] : 'вашем городе';
?>

<section class="hero"<?= $heroUrl ? ' style="background-image:url(\''.htmlspecialcharsbx($heroUrl).'\')"' : '' ?>>
    <div class="container">
        <div class="hero__content">
            <h1 class="hero__title"><?= htmlspecialcharsbx($seoH1) ?></h1>
            <?php if (!empty($arSection['DESCRIPTION'])): ?>
            <p class="hero__subtitle"><?= htmlspecialcharsbx(strip_tags($arSection['DESCRIPTION'])) ?></p>
            <?php endif; ?>
            <button type="button" class="hero__btn js-request-form">Заказать расчёт</button>
        </div>

        <? // Разметка как на главной (index.php): грид 3-в-ряд живёт на .hero__features-track,
           // на смартфоне тот же трек превращается в карусель с точками (main.js, [data-carousel]) ?>
        <div class="hero__features" data-carousel>
            <ul class="hero__features-track" data-carousel-track>
                <li class="feature">
                    <span class="feature__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.4 13.9 17 22l-5-3-5 3 1.6-8.1"/></svg>
                    </span>
                    <span class="feature__text">
                        <span class="feature__title">Latitudo</span>
                        <span class="feature__desc">производитель и поставщик ДПК с 2014 года</span>
                    </span>
                </li>
                <li class="feature">
                    <span class="feature__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v5c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                    <span class="feature__text">
                        <span class="feature__title">До 25 лет</span>
                        <span class="feature__desc">гарантия на продукцию</span>
                    </span>
                </li>
                <li class="feature">
                    <span class="feature__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12v9M12 12 4 7.5"/></svg>
                    </span>
                    <span class="feature__text">
                        <span class="feature__title">Материалы в наличии</span>
                        <span class="feature__desc">на складе в <?= htmlspecialcharsbx($heroCityIn) ?></span>
                    </span>
                </li>
            </ul>
            <div class="carousel-dots carousel-dots--light" data-carousel-dots aria-hidden="true"></div>
        </div>
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
                "PROPERTY_CODE"       => ["GALLERY", "PRICE_CURRENT", "PRICE_OLD", "GARANTY", "FREE_DOSTAVKA", "IN_STOCK"],
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

<?php // Отзывы — общий блок; скрывается галочкой UF_SHOW_REVIEWS у раздела в админке
latitudoShowReviewsForSection($sectionCode); ?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
