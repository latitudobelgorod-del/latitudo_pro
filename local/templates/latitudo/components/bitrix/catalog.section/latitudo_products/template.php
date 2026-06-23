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
$sectionCode = $arParams['SECTION_CODE'] ?? ($arResult['SECTION']['CODE'] ?? '');

if ($sectionCode && \Bitrix\Main\Loader::includeModule('iblock')) {
    $rsHero = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $arParams['IBLOCK_ID'] ?? 3, 'CODE' => $sectionCode, 'ACTIVE' => 'Y'],
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
            <button type="button" class="hero__btn" data-stub="request">Заказать расчёт</button>
        </div>

        <ul class="hero__features">
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
    </div>
</section>

<?php // ── Сетка товаров ──────────────────────────────────────────────────────
if (empty($arResult['ITEMS'])): ?>
<section class="section products-section" id="catalog">
    <div class="container">
        <p class="products-empty">Товары в этом разделе скоро появятся.</p>
    </div>
</section>
<?php return; endif; ?>

<?php
// catalog.section не передаёт свойства элементов — фетчим одним батч-запросом
$iblockIdItems = (int)($arParams['IBLOCK_ID'] ?? 3);
$elemIds       = array_column($arResult['ITEMS'], 'ID');
$galleryMap    = [];
$priceNewMap   = [];
$priceOldMap   = [];
if ($elemIds) {
    $rsEl = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        ['=ID' => $elemIds, 'IBLOCK_ID' => $iblockIdItems],
        false,
        false,
        false
    );
    while ($el = $rsEl->GetNextElement(false, false)) {
        $f   = $el->GetFields();
        $p   = $el->GetProperties();
        $eid = (int)$f['ID'];
        $vals = $p['GALLERY']['VALUE'] ?? null;
        $galleryMap[$eid]  = is_array($vals) ? array_values(array_filter($vals)) : ($vals ? [$vals] : []);
        $priceNewMap[$eid] = $p['PRICE_CURRENT']['VALUE'] ?? '';
        $priceOldMap[$eid] = $p['PRICE_OLD']['VALUE']     ?? '';
    }
}
?>
<section class="section products-section" id="catalog">
<div class="container">
    <h2 class="section__title">Товары и цены</h2>
    <div class="products-grid">
    <?php foreach ($arResult['ITEMS'] as $arItem):
        $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'));

        $eid = (int)$arItem['ID'];
        $galleryImages = [];
        foreach ($galleryMap[$eid] ?? [] as $fid) {
            if (!$fid) continue;
            $src = is_array($fid) ? ($fid['SRC'] ?? CFile::GetPath($fid['VALUE'] ?? 0)) : CFile::GetPath($fid);
            if ($src) $galleryImages[] = $src;
        }
        if (empty($galleryImages) && !empty($arItem['PREVIEW_PICTURE']['SRC'])) {
            $galleryImages[] = $arItem['PREVIEW_PICTURE']['SRC'];
        }

        $priceNew  = $priceNewMap[$eid] ?? '';
        $priceOld  = $priceOldMap[$eid] ?? '';
        $hasSlider = count($galleryImages) > 1;
    ?>
        <div class="product-card" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">

            <div class="swiper product-card__slider<?= $hasSlider ? '' : ' product-card__slider--single' ?>">
                <div class="swiper-wrapper">
                    <?php if (!empty($galleryImages)): ?>
                        <?php foreach ($galleryImages as $imgSrc): ?>
                        <div class="swiper-slide">
                            <img src="<?= htmlspecialcharsbx($imgSrc) ?>"
                                 alt="<?= htmlspecialcharsbx($arItem['NAME']) ?>"
                                 loading="lazy"
                                 class="product-card__img">
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="swiper-slide product-card__no-photo"><span>Фото скоро</span></div>
                    <?php endif; ?>
                </div>
                <?php if ($hasSlider): ?>
                <button class="swiper-button-prev product-slider-btn" aria-label="Назад"></button>
                <button class="swiper-button-next product-slider-btn" aria-label="Вперёд"></button>
                <?php endif; ?>
            </div>

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

            <button class="product-card__btn js-order-btn"
                    type="button"
                    data-product="<?= htmlspecialcharsbx($arItem['NAME']) ?>">
                Заказать расчёт
            </button>
        </div>
    <?php endforeach; ?>
    </div>
</div>
</section>

<?php // ── Попап «Заказать расчёт» (один раз на страницу) ────────────────────
if (!defined('LATITUDO_ORDER_MODAL')): define('LATITUDO_ORDER_MODAL', true); ?>
<div class="modal-order" id="modal-order" role="dialog" aria-modal="true" aria-labelledby="modal-order-title" hidden>
    <div class="modal-order__overlay js-modal-close"></div>
    <div class="modal-order__dialog">
        <button class="modal-order__close js-modal-close" aria-label="Закрыть">&times;</button>
        <h3 class="modal-order__title" id="modal-order-title">Заказать расчёт</h3>
        <p class="modal-order__product" id="modal-order-product"></p>
        <form class="modal-order__form" id="modal-order-form" novalidate>
            <div class="modal-order__field">
                <label class="modal-order__label" for="order-name">Ваше имя</label>
                <input class="modal-order__input" type="text" id="order-name" name="name" required placeholder="Имя">
            </div>
            <div class="modal-order__field">
                <label class="modal-order__label" for="order-phone">Телефон</label>
                <input class="modal-order__input" type="tel" id="order-phone" name="phone" required placeholder="+7 (___) ___-__-__">
            </div>
            <input type="hidden" name="product" id="order-product-hidden">
            <p class="modal-order__success" id="modal-order-success" hidden>
                Заявка отправлена! Мы свяжемся с вами в ближайшее время.
            </p>
            <button class="modal-order__submit" type="submit">Отправить заявку</button>
        </form>
    </div>
</div>

<script>
(function () {
    // Слайдеры Swiper
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
    });

    // Модальное окно
    var modal      = document.getElementById('modal-order');
    var form       = document.getElementById('modal-order-form');
    var productEl  = document.getElementById('modal-order-product');
    var productHid = document.getElementById('order-product-hidden');
    var success    = document.getElementById('modal-order-success');

    function openModal(name) {
        productEl.textContent = name;
        productHid.value      = name;
        form.reset();
        success.hidden = true;
        form.querySelector('.modal-order__submit').hidden = false;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.js-order-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { openModal(btn.getAttribute('data-product') || ''); });
    });
    document.querySelectorAll('.js-modal-close').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        fetch('/include/ajax-order.php', { method: 'POST', body: new FormData(form) })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.ok) {
                    success.hidden = false;
                    form.querySelector('.modal-order__submit').hidden = true;
                } else {
                    alert(res.error || 'Ошибка. Позвоните нам по телефону.');
                }
            })
            .catch(function () { alert('Ошибка сети. Позвоните нам по телефону.'); });
    });
})();
</script>
<?php endif; ?>
