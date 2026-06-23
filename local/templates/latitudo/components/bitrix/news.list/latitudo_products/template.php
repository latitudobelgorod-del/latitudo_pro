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
                    <img src="<?= htmlspecialcharsbx($imgSrc) ?>"
                         alt="<?= htmlspecialcharsbx($arItem['NAME']) ?>"
                         loading="lazy"
                         class="product-card__img">
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

        <button class="product-card__btn js-order-btn"
                type="button"
                data-product="<?= htmlspecialcharsbx($arItem['NAME']) ?>">
            Заказать расчёт
        </button>
    </div>
<?php endforeach; ?>
</div>

<!-- Модальное окно (один раз на страницу) -->
<?php if (!defined('LATITUDO_ORDER_MODAL')): define('LATITUDO_ORDER_MODAL', true); ?>
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
    // Инициализация слайдеров
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
    var modal = document.getElementById('modal-order');
    var form  = document.getElementById('modal-order-form');
    var productEl  = document.getElementById('modal-order-product');
    var productHid = document.getElementById('order-product-hidden');
    var success    = document.getElementById('modal-order-success');

    function openModal(productName) {
        productEl.textContent  = productName;
        productHid.value       = productName;
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
        btn.addEventListener('click', function () {
            openModal(btn.getAttribute('data-product') || '');
        });
    });

    document.querySelectorAll('.js-modal-close').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var data = new FormData(form);
        fetch('/include/ajax-order.php', { method: 'POST', body: data })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.ok) {
                    success.hidden = false;
                    form.querySelector('.modal-order__submit').hidden = true;
                } else {
                    alert(res.error || 'Ошибка. Позвоните нам по телефону.');
                }
            })
            .catch(function () {
                alert('Ошибка сети. Позвоните нам по телефону.');
            });
    });
})();
</script>
<?php endif; ?>
