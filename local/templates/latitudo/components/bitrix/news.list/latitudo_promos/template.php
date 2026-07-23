<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Блок «Акции месяца» (Figma, раунд 4: компонент 537:20390 — десктоп, 537:40113 — смартфон).
 * Баннер акции — готовая картинка от дизайнеров (все тексты уже на ней).
 * Клик по баннеру открывает попап с текстом «Подробнее об условиях» (Fancybox inline,
 * подключён глобально в header.php — тот же механизм, что у попапов отзывов).
 * Десктоп: 2 баннера в ряд + круглые стрелки. Смартфон: свайп + точки.
 *
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */
$this->setFrameMode(true);

// Оставляем только акции с баннером (без картинки карточке нечего показывать) —
// от их числа зависит раскладка.
$promoItems = array_values(array_filter(
    $arResult['ITEMS'],
    static fn($i) => !empty($i['PREVIEW_PICTURE']['SRC'])
));
if (empty($promoItems)) {
    return; // нет действующих акций — секцию не рисуем вообще
}
// Раскладка по количеству: 1 — баннер на всю ширину, 2 — два в ряд без слайдера,
// 3+ — слайдер со стрелками. Разводится классами в CSS (ширина слайда, стрелки).
$promoCount = count($promoItems);
$promoMod   = $promoCount === 1 ? 'promos--single' : ($promoCount === 2 ? 'promos--pair' : 'promos--slider');
?>
<section class="section promos" id="promos">
    <div class="container">
        <h2 class="section__title">Акции месяца</h2>

        <div class="promos__carousel <?= $promoMod ?>" data-promos>
            <button type="button" class="promos__arrow promos__arrow--prev" data-promos-prev aria-label="Предыдущие акции">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#6B6B6B" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
            </button>

            <ul class="promos__track" data-promos-track>
                <? foreach ($promoItems as $arItem):
                    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
                    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'));

                    $itemId  = (int)$arItem['ID'];
                    $picture = $arItem['PREVIEW_PICTURE'] ?? null;
                    if (empty($picture['SRC'])) {
                        continue; // баннер обязателен: без картинки карточке нечего показывать
                    }
                    $hasTerms = trim(strip_tags((string)($arItem['DETAIL_TEXT'] ?? ''))) !== '';
                ?>
                <li class="promos__slide" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
                    <? if ($hasTerms): ?>
                        <? // Вся карточка — кнопка: надпись «Подробнее об условиях» уже нарисована на баннере ?>
                        <button type="button" class="promo-card"
                                data-fancybox="promo-terms-<?= $itemId ?>"
                                data-src="#promo-terms-<?= $itemId ?>" data-type="inline"
                                aria-label="<?= htmlspecialcharsbx($arItem['NAME']) ?> — подробнее об условиях">
                            <img class="promo-card__img" src="<?= htmlspecialcharsbx($picture['SRC']) ?>"
                                 width="<?= (int)$picture['WIDTH'] ?>" height="<?= (int)$picture['HEIGHT'] ?>"
                                 loading="lazy" alt="<?= htmlspecialcharsbx($arItem['NAME']) ?>">
                        </button>

                        <? // Условия акции — скрыты, открываются в попапе (Fancybox inline) ?>
                        <div class="promo-modal" id="promo-terms-<?= $itemId ?>" style="display:none">
                            <h3 class="promo-modal__title"><?= htmlspecialcharsbx($arItem['NAME']) ?></h3>
                            <div class="promo-modal__text"><?= $arItem['DETAIL_TEXT'] ?></div>
                            <button type="button" class="promo-modal__close">Закрыть</button>
                        </div>
                    <? else: ?>
                        <div class="promo-card promo-card--static">
                            <img class="promo-card__img" src="<?= htmlspecialcharsbx($picture['SRC']) ?>"
                                 width="<?= (int)$picture['WIDTH'] ?>" height="<?= (int)$picture['HEIGHT'] ?>"
                                 loading="lazy" alt="<?= htmlspecialcharsbx($arItem['NAME']) ?>">
                        </div>
                    <? endif ?>
                </li>
                <? endforeach ?>
            </ul>

            <button type="button" class="promos__arrow promos__arrow--next" data-promos-next aria-label="Следующие акции">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#1F1F1F" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6"/>
                </svg>
            </button>

            <div class="promos__dots carousel-dots" data-promos-dots aria-hidden="true"></div>
        </div>
    </div>
</section>

<script>
(function () {
    var root = document.querySelector('[data-promos]');
    if (!root) return;

    var track = root.querySelector('[data-promos-track]');
    var prev  = root.querySelector('[data-promos-prev]');
    var next  = root.querySelector('[data-promos-next]');
    var dots  = root.querySelector('[data-promos-dots]');
    var slides = track.querySelectorAll('.promos__slide');
    if (!slides.length) return;

    /* Шаг прокрутки = ширина баннера + отступ между баннерами */
    function step() {
        if (slides.length < 2) return slides[0].offsetWidth;
        return slides[1].offsetLeft - slides[0].offsetLeft;
    }

    /* Точки (нужны на смартфоне, где баннер один в кадре); одному слайду точки не нужны */
    if (slides.length < 2) dots.style.display = 'none';
    slides.forEach(function (_, i) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'carousel-dots__dot';
        dot.setAttribute('aria-label', 'Акция ' + (i + 1));
        dot.addEventListener('click', function () {
            track.scrollTo({ left: slides[i].offsetLeft - track.offsetLeft, behavior: 'smooth' });
        });
        dots.appendChild(dot);
    });
    var dotList = dots.querySelectorAll('.carousel-dots__dot');

    /* Активная точка + гашение стрелок на краях */
    function sync() {
        var i = Math.round(track.scrollLeft / step());
        dotList.forEach(function (d, n) { d.classList.toggle('is-active', n === i); });

        var maxScroll = track.scrollWidth - track.clientWidth - 1;
        prev.disabled = track.scrollLeft <= 0;
        next.disabled = track.scrollLeft >= maxScroll;
    }

    prev.addEventListener('click', function () {
        track.scrollBy({ left: -step(), behavior: 'smooth' });
    });
    next.addEventListener('click', function () {
        track.scrollBy({ left: step(), behavior: 'smooth' });
    });

    var raf;
    track.addEventListener('scroll', function () {
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(sync);
    }, { passive: true });
    window.addEventListener('resize', sync);
    sync();

    /* Кнопка «Закрыть» внутри попапа условий */
    document.addEventListener('click', function (e) {
        if (e.target.closest('.promo-modal__close') && window.Fancybox) Fancybox.close();
    });

    /* Попапы условий: Fancybox 5 требует явной привязки (скрипт грузится с defer).
       mainClass — как у правовых попапов: fancybox.css грузится ПОСЛЕ styles.css и без
       этой метки перебивает наше скругление и выносит крестик за рамку окна. */
    function bindPromos() { Fancybox.bind('[data-fancybox^="promo-"]', { mainClass: 'fancybox-doc', Thumbs: false }); }
    if (window.Fancybox) bindPromos();
    else window.addEventListener('load', function () { if (window.Fancybox) bindPromos(); });
})();
</script>
