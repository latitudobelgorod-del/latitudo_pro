<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?php
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponentTemplate $this */
$this->setFrameMode(true);

// ── Hero ─────────────────────────────────────────────────────────────────────
// Запрашиваем все поля раздела напрямую — catalog.section не всегда возвращает NAME/DESCRIPTION
$heroUrl     = '';
$sectionName = '';
$sectionDesc = '';
// Страницы лендингов передают SECTION_ID (резолвится по стабильному якорю,
// см. local/php_interface/include/catalog-sections.php); SECTION_CODE — запасной путь.
$sectionId   = (int)($arParams['SECTION_ID'] ?? ($arResult['SECTION']['ID'] ?? 0));
$sectionCode = $arParams['SECTION_CODE'] ?? ($arResult['SECTION']['CODE'] ?? '');
$heroFilter  = $sectionId ? ['ID' => $sectionId] : ($sectionCode ? ['CODE' => $sectionCode] : null);

if ($heroFilter && \Bitrix\Main\Loader::includeModule('iblock')) {
    $rsHero = CIBlockSection::GetList(
        [],
        $heroFilter + ['IBLOCK_ID' => $arParams['IBLOCK_ID'] ?? 3, 'ACTIVE' => 'Y'],
        false,
        ['ID', 'NAME', 'DESCRIPTION', 'PICTURE', 'DETAIL_PICTURE']
    );
    if ($arHero = $rsHero->GetNext(false, false)) {
        $sectionDesc = $arHero['DESCRIPTION'];
        $iprop = new \Bitrix\Iblock\InheritedProperty\SectionValues(
            (int)($arParams['IBLOCK_ID'] ?? 3), (int)$arHero['ID']
        );
        $ipropVals   = $iprop->getValues();
        $sectionName = !empty($ipropVals['SECTION_PAGE_TITLE'])
            ? $ipropVals['SECTION_PAGE_TITLE']
            : $arHero['NAME'];
        $fileId = $arHero['DETAIL_PICTURE'] ?: $arHero['PICTURE'];
        if ($fileId) {
            $arFile = CFile::GetFileArray($fileId);
            if ($arFile) $heroUrl = $arFile['SRC'];
        }
    }
}
// Запасной вариант из данных компонента
if (!$sectionName) $sectionName = $arResult['SECTION']['NAME'] ?? '';
if (!$sectionDesc) $sectionDesc = $arResult['SECTION']['DESCRIPTION'] ?? '';
if (!$heroUrl) {
    $rawPic = ($arResult['SECTION']['DETAIL_PICTURE'] ?: ($arResult['SECTION']['PICTURE'] ?? null));
    if (is_array($rawPic) && !empty($rawPic['SRC'])) $heroUrl = $rawPic['SRC'];
}

// Необязательный override отображаемого имени раздела (параметр DISPLAY_NAME страницы),
// если заголовок лендинга должен отличаться от названия раздела в админке.
if (!empty($arParams['DISPLAY_NAME'])) $sectionName = $arParams['DISPLAY_NAME'];

$APPLICATION->SetTitle($sectionName);

$heroStore  = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
$heroCityIn = ($heroStore && !empty($heroStore['CITY_IN'])) ? $heroStore['CITY_IN'] : 'вашем городе';
?>

<section class="hero"<?= $heroUrl ? ' style="background-image:url(\''.htmlspecialcharsbx($heroUrl).'\')"' : '' ?>>
    <div class="container">
        <div class="hero__content">
            <h1 class="hero__title"><?= htmlspecialcharsbx($sectionName) ?></h1>
            <?php if (!empty($sectionDesc)): ?>
            <p class="hero__subtitle"><?= htmlspecialcharsbx(strip_tags($sectionDesc)) ?></p>
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

<?php
// ── Блоки раздела между hero и каталогом ─────────────────────────────────────
// Figma: перила 537:23893, заборы — «История одного забора» 537:24696 и
// «Заборы из ДПК» 537:24697 (именно в таком порядке).
// AFTER_HERO_INCLUDE принимает либо один путь строкой (старый вызов), либо список
// блоков; каждый элемент списка — путь строкой или массив PATH/CLASS/ID/NAME.
// Внимание: вывод компонента кэшируется (CACHE_TIME), правки текста в админке
// появятся на сайте после сброса кэша либо по истечении времени кэширования.
$afterHero = $arParams['AFTER_HERO_INCLUDE'] ?? '';
$afterHeroBlocks = [];
foreach (is_array($afterHero) ? $afterHero : [$afterHero] as $block) {
    $block = is_array($block) ? $block : ['PATH' => $block];
    $path  = (string)($block['PATH'] ?? '');
    if ($path === '' || !file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
        continue;
    }
    $afterHeroBlocks[] = [
        'PATH'  => $path,
        'CLASS' => (string)($block['CLASS'] ?? 'benefits'),
        'ID'    => (string)($block['ID'] ?? 'benefits'),
        'NAME'  => (string)($block['NAME'] ?? 'Блок «Преимущества раздела»'),
    ];
}
foreach ($afterHeroBlocks as $block): ?>
<section class="section <?= htmlspecialcharsbx($block['CLASS']) ?>" id="<?= htmlspecialcharsbx($block['ID']) ?>">
    <div class="container">
        <?php $APPLICATION->IncludeFile(
            $block['PATH'],
            [],
            ['MODE' => 'html', 'NAME' => $block['NAME']]
        ); ?>
    </div>
</section>
<?php endforeach; ?>

<?php // ── Сетка товаров ──────────────────────────────────────────────────────
// Пустой раздел — блок не выводим совсем (страница «схлопывается»).
// Остаётся только якорь нулевой высоты: прямые ссылки вида /zabory/#catalog
// не должны отваливаться. Пункт меню «Цены» в этом случае скрыт,
// см. latitudoShowCatalogMenuItem() в include/catalog-sections.php.
if (empty($arResult['ITEMS'])): ?>
<div id="catalog"></div>
<?php return; endif; ?>

<?php
// catalog.section не передаёт свойства элементов — фетчим одним батч-запросом
$iblockIdItems = (int)($arParams['IBLOCK_ID'] ?? 3);
$elemIds       = array_column($arResult['ITEMS'], 'ID');
$propsMap      = [];
if ($elemIds) {
    $rsEl = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        ['=ID' => $elemIds, 'IBLOCK_ID' => $iblockIdItems],
        false,
        false,
        false
    );
    while ($el = $rsEl->GetNextElement(false, false)) {
        $propsMap[(int)$el->GetFields()['ID']] = $el->GetProperties();
    }
}
?>
<section class="section products-section" id="catalog">
<div class="container">
    <h2 class="section__title">Товары и цены</h2>
    <?php
    // Разметка карточки и ленты — в include/product-card.php (один экземпляр на весь сайт)
    latitudoProductsGridOpen();
    foreach ($arResult['ITEMS'] as $arItem) {
        $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'));

        latitudoRenderProductCard(
            latitudoProductCardData($arItem, $propsMap[(int)$arItem['ID']] ?? [], $this->GetEditAreaId($arItem['ID']))
        );
    }
    latitudoProductsGridClose();
    ?>
</div>
</section>

<?php latitudoProductsSliderJs(); ?>
