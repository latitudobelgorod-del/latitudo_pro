<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?php
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponentTemplate $this */
$this->setFrameMode(true);

if (empty($arResult['ITEMS'])) {
    echo '<p class="products-empty">Товары в этом разделе скоро появятся.</p>';
    return;
}
?>

<div class="products-grid">
<?php foreach ($arResult['ITEMS'] as $arItem):
    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'));

    // Галерея: свойство GALLERY — множественный файл
    $galleryImages = [];
    if (!empty($arItem['PROPERTIES']['GALLERY']['VALUE'])) {
        $rawVals = (array)$arItem['PROPERTIES']['GALLERY']['VALUE'];
        foreach ($rawVals as $fid) {
            if (!$fid) continue;
            // VALUE может быть ID файла (int) или уже массивом с SRC
            if (is_array($fid)) {
                if (!empty($fid['SRC'])) $galleryImages[] = $fid['SRC'];
            } else {
                $src = CFile::GetPath($fid);
                if ($src) $galleryImages[] = $src;
            }
        }
    }
    // Если галерея пустая — берём превью-картинку
    if (empty($galleryImages) && !empty($arItem['PREVIEW_PICTURE']['SRC'])) {
        $galleryImages[] = $arItem['PREVIEW_PICTURE']['SRC'];
    }

    $priceNew = $arItem['PROPERTIES']['PRICE_CURRENT']['VALUE'] ?? '';
    $priceOld = $arItem['PROPERTIES']['PRICE_OLD']['VALUE'] ?? '';
    $hasSlider = count($galleryImages) > 1;
    $cardId = 'product-' . $arItem['ID'];
?>
    <div class="product-card" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">

        <!-- Слайдер изображений -->
        <div class="swiper product-card__slider<?= $hasSlider ? '' : ' product-card__slider--single' ?>" id="<?= htmlspecialcharsbx($cardId) ?>">
            <div class="swiper-wrapper">
                <?php foreach ($galleryImages as $imgSrc): ?>
                <div class="swiper-slide">
                    <a href="<?= htmlspecialcharsbx($imgSrc) ?>"
                       data-fancybox="gallery-<?= (int)$arItem['ID'] ?>"
                       data-caption="<?= htmlspecialcharsbx($arItem['NAME']) ?>">
                        <img src="<?= htmlspecialcharsbx($imgSrc) ?>"
                             alt="<?= htmlspecialcharsbx($arItem['NAME']) ?>"
                             loading="lazy"
                             class="product-card__img">
                    </a>
                </div>
                <?php endforeach; ?>
                <?php if (empty($galleryImages)): ?>
                <div class="swiper-slide product-card__no-photo">
                    <span>Фото скоро</span>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($hasSlider): ?>
            <button class="swiper-button-prev product-slider-btn" aria-label="Назад"></button>
            <button class="swiper-button-next product-slider-btn" aria-label="Вперёд"></button>
            <?php endif; ?>
        </div>

        <!-- Контент карточки -->
        <div class="product-card__body">
            <h3 class="product-card__title"><?= htmlspecialcharsbx($arItem['NAME']) ?></h3>

            <?php if (!empty($arItem['PREVIEW_TEXT'])): ?>
            <p class="product-card__desc"><?= htmlspecialcharsbx($arItem['PREVIEW_TEXT']) ?></p>
            <?php endif; ?>

            <?php if ($priceNew || $priceOld): ?>
            <div class="product-card__prices">
                <?php if ($priceNew): ?>
                <span class="product-card__price-new"><?= htmlspecialcharsbx($priceNew) ?></span>
                <?php endif; ?>
                <?php if ($priceOld): ?>
                <span class="product-card__price-old"><?= htmlspecialcharsbx($priceOld) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <button class="product-card__btn js-request-form" type="button">
            Заказать расчёт
        </button>
    </div>
<?php endforeach; ?>
</div>

<!-- Слайдеры товаров + лайтбокс галереи (один раз на страницу).
     Заявку принимает единая «Форма заявки» (footer.php → latitudoShowRequestForm):
     кнопки товаров открывают её через data-fancybox="request-form". -->
<?php if (!defined('LATITUDO_PRODUCTS_JS')): define('LATITUDO_PRODUCTS_JS', true); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.product-card__slider:not(.product-card__slider--single)').forEach(function (el) {
        new Swiper(el, {
            loop: true,
            navigation: {
                nextEl: el.querySelector('.swiper-button-next'),
                prevEl: el.querySelector('.swiper-button-prev'),
            }
        });
    });
    Fancybox.bind('[data-fancybox^="gallery-"]', { groupAll: false });
});
</script>
<?php endif; ?>
