<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Карточка товара и лента карточек — ОДНА разметка на весь сайт.
 *
 * ЗАЧЕМ. Карточка нужна в трёх местах: шаблон catalog.section (шесть лендингов
 * разделов), шаблон news.list (/catalog/) и блок «С этими товарами покупают».
 * Раньше её вёрстка была скопирована в двух шаблонах и уже начинала расходиться —
 * та же беда, из-за которой в отдельный файл уехали ярлыки (catalog-badges.php).
 *
 * Макет: Figma 537:22961 (Product Card, раунд 4);
 *        десктоп — 3 в ряд, gap 24 (537:19714);
 *        смартфон — горизонтальная лента с точками, карточка 350 из 430 (237:12154).
 */

/**
 * Элемент инфоблока → нормализованные данные карточки.
 *
 * $item — массив элемента. Свойства могут прийти двумя путями:
 *   news.list       → $item['PROPERTIES']
 *   catalog.section → свойств не отдаёт, шаблон дофетчивает их сам и кладёт в $props.
 * Поэтому источник свойств передаётся отдельным аргументом.
 */
function latitudoProductCardData(array $item, array $props = [], string $editAreaId = ''): array
{
    $images = [];
    foreach ((array)($props['GALLERY']['VALUE'] ?? []) as $fid) {
        if (!$fid) {
            continue;
        }
        // VALUE — либо ID файла, либо уже массив с SRC (зависит от компонента)
        $src = is_array($fid)
            ? ($fid['SRC'] ?? CFile::GetPath($fid['VALUE'] ?? 0))
            : CFile::GetPath($fid);
        if ($src) {
            $images[] = $src;
        }
    }
    if (!$images && !empty($item['PREVIEW_PICTURE']['SRC'])) {
        $images[] = $item['PREVIEW_PICTURE']['SRC'];
    }
    if (!$images && !empty($item['PREVIEW_PICTURE']) && !is_array($item['PREVIEW_PICTURE'])) {
        $src = CFile::GetPath($item['PREVIEW_PICTURE']);
        if ($src) {
            $images[] = $src;
        }
    }

    return [
        'id'           => (int)$item['ID'],
        'name'         => (string)$item['NAME'],
        'preview_text' => (string)($item['PREVIEW_TEXT'] ?? ''),
        'images'       => $images,
        'price_new'    => (string)($props['PRICE_CURRENT']['VALUE'] ?? ''),
        'price_old'    => (string)($props['PRICE_OLD']['VALUE'] ?? ''),
        'badges'       => latitudoProductBadges($props),
        'edit_area_id' => $editAreaId,
    ];
}

/** Одна карточка товара. Данные — из latitudoProductCardData(). */
function latitudoRenderProductCard(array $card): void
{
    $hasSlider = count($card['images']) > 1;
    ?>
    <div class="product-card"<?= $card['edit_area_id'] !== '' ? ' id="' . htmlspecialcharsbx($card['edit_area_id']) . '"' : '' ?>>

        <div class="swiper product-card__slider<?= $hasSlider ? '' : ' product-card__slider--single' ?>">
            <div class="swiper-wrapper">
                <? if ($card['images']): ?>
                    <? foreach ($card['images'] as $src): ?>
                    <div class="swiper-slide">
                        <a href="<?= htmlspecialcharsbx($src) ?>"
                           data-fancybox="gallery-<?= $card['id'] ?>"
                           data-caption="<?= htmlspecialcharsbx($card['name']) ?>">
                            <img src="<?= htmlspecialcharsbx($src) ?>"
                                 alt="<?= htmlspecialcharsbx($card['name']) ?>"
                                 loading="lazy"
                                 class="product-card__img">
                        </a>
                    </div>
                    <? endforeach ?>
                <? else: ?>
                    <div class="swiper-slide product-card__no-photo"><span>Фото скоро</span></div>
                <? endif ?>
            </div>
            <? if ($hasSlider): ?>
            <button class="swiper-button-prev product-slider-btn" aria-label="Назад"></button>
            <button class="swiper-button-next product-slider-btn" aria-label="Вперёд"></button>
            <? endif ?>
        </div>

        <? // Ярлыки лежат поверх фото и позиционируются от самой карточки (Figma: Frame 31 —
           // ABSOLUTE-ребёнок Product Card). Внутрь слайдера их класть нельзя: там хозяйничает Swiper.
           latitudoRenderProductBadges($card['badges']); ?>

        <div class="product-card__body">
            <h3 class="product-card__title"><?= htmlspecialcharsbx($card['name']) ?></h3>

            <? if ($card['preview_text'] !== ''): ?>
            <p class="product-card__desc"><?= htmlspecialcharsbx($card['preview_text']) ?></p>
            <? endif ?>

            <? if ($card['price_new'] || $card['price_old'] || $card['badges']['in_stock']): ?>
            <div class="product-card__pricerow">
                <div class="product-card__prices">
                    <? if ($card['price_new']): ?>
                    <span class="product-card__price-new"><?= htmlspecialcharsbx($card['price_new']) ?></span>
                    <? endif ?>
                    <? if ($card['price_old']): ?>
                    <span class="product-card__price-old"><?= htmlspecialcharsbx($card['price_old']) ?></span>
                    <? endif ?>
                </div>
                <? latitudoRenderProductStock($card['badges']); ?>
            </div>
            <? endif ?>
        </div>

        <button class="product-card__btn js-request-form" type="button">
            Заказать расчёт
        </button>
    </div>
    <?
}

/**
 * Открыть ленту карточек. Десктоп — сетка 3 → 2 колонки (сеткой рулит __track),
 * смартфон — горизонтальная карусель со scroll-snap и точками (JS общий,
 * main.js ищет любой [data-carousel] и сам рисует точки).
 */
function latitudoProductsGridOpen(): void
{
    ?>
    <div class="products-grid" data-carousel>
        <div class="products-grid__track" data-carousel-track>
    <?
}

function latitudoProductsGridClose(): void
{
    ?>
        </div>
        <div class="carousel-dots" data-carousel-dots aria-hidden="true"></div>
    </div>
    <?
}

/**
 * Слайдеры фото внутри карточек + лайтбокс.
 *
 * ЗАЩИТА ОТ ДВОЙНОЙ ИНИЦИАЛИЗАЦИИ — на двух уровнях, и оба нужны.
 * Константа отсекает повтор в пределах одного запроса, но у catalog.section кэш на час:
 * при попадании в кэш PHP не выполняется, скрипт приезжает уже внутри готового HTML,
 * константа не определена — и блок «С этими товарами покупают» напечатал бы тег второй раз.
 * Поэтому сам скрипт тоже идемпотентен (флаг в window + проверка el.swiper): два одинаковых
 * тега на странице безвредны, второй Swiper поверх первого не создаётся.
 */
function latitudoProductsSliderJs(): void
{
    if (defined('LATITUDO_PRODUCTS_JS')) {
        return;
    }
    define('LATITUDO_PRODUCTS_JS', true);
    ?>
    <script>
    (function () {
        if (window.__latitudoProductsInit) return;
        window.__latitudoProductsInit = true;

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.product-card__slider:not(.product-card__slider--single)').forEach(function (el) {
                if (el.swiper) return;
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
    })();
    </script>
    <?
}
