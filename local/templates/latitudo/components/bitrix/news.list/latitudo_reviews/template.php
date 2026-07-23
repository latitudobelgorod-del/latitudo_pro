<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Блок «Отзывы» (Figma, раунд 4: узел 2002:26168 — десктоп, 2002:26599 — смартфон).
 * Десктоп: 3 карточки в ряд + круглые стрелки. Смартфон: свайп + точки.
 * Фото в карточке открываются в Fancybox (подключён глобально в header.php).
 *
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */
$this->setFrameMode(true);

if (empty($arResult['ITEMS'])) {
    return; // нет опубликованных отзывов — секцию не рисуем вообще
}

// Бейдж рейтинга Яндекс.Карт (src уже безопасно пересобран в include/reviews.php).
$badgeSrc    = trim((string)($arParams['YANDEX_BADGE_SRC'] ?? ''));
// Запасной числовой рейтинг из инфоблока «Магазины» (если бейджа для региона нет).
$ratingValue = trim((string)($arParams['YANDEX_RATING'] ?? ''));
$ratingCount = (int)($arParams['YANDEX_RATING_COUNT'] ?? 0);
$reviewsUrl  = trim((string)($arParams['YANDEX_REVIEWS_URL'] ?? ''));
$hasBadge    = $badgeSrc !== '';
$hasRating   = $hasBadge || $ratingValue !== '';

$currentYear = (int)date('Y');

/** 5 звёзд: закрашено $filled штук. */
$renderStars = static function (int $filled, int $size): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $color = $i <= $filled ? '#FFC500' : '#D9D9D9';
        $out .= '<svg class="stars__item" width="' . $size . '" height="' . $size . '" viewBox="0 0 20 20" fill="' . $color . '" aria-hidden="true">'
            . '<path d="M10 0l2.6 6.6 7.4.4-5.7 4.6 1.9 7-6.2-4-6.2 4 1.9-7L0 7l7.4-.4z"/></svg>';
    }
    return $out;
};
?>
<section class="section reviews" id="reviews">
    <div class="container">
        <div class="section__head">
            <h2 class="section__title">Отзывы</h2>
            <p class="section__subtitle">Вы можете проверить их на Яндексе</p>
        </div>

        <? if ($hasRating): ?>
        <div class="reviews__summary">
            <div class="reviews__score">
                <? if ($hasBadge): ?>
                <? // Официальный бейдж рейтинга Яндекс.Карт. src пересобран из проверенного
                   // org-id (только цифры) в include/reviews.php — произвольный HTML из поля сюда не попадает. ?>
                <iframe class="reviews__badge" src="<?= htmlspecialcharsbx($badgeSrc) ?>"
                        width="150" height="50" frameborder="0" loading="lazy"
                        title="Рейтинг организации на Яндекс.Картах"></iframe>
                <? else: ?>
                <span class="reviews__score-value"><?= htmlspecialcharsbx($ratingValue) ?></span>
                <span class="reviews__score-meta">
                    <span class="stars stars--lg" role="img"
                          aria-label="Рейтинг <?= htmlspecialcharsbx($ratingValue) ?> из 5">
                        <?= $renderStars(5, 20) ?>
                    </span>
                    <? if ($ratingCount > 0): ?>
                    <span class="reviews__count">
                        <svg class="reviews__ya-pin" width="14" height="17" viewBox="0 0 14 17" aria-hidden="true">
                            <path fill="#FC3F1D" d="M7 0a7 7 0 0 0-7 7c0 5.1 6.3 9.6 6.6 9.8a.7.7 0 0 0 .8 0C7.7 16.6 14 12.1 14 7a7 7 0 0 0-7-7Zm0 9.6A2.6 2.6 0 1 1 7 4.4a2.6 2.6 0 0 1 0 5.2Z"/>
                        </svg>
                        <?= $ratingCount ?>&nbsp;<?= latitudoPluralRatings($ratingCount) ?>
                    </span>
                    <? endif ?>
                </span>
                <? endif ?>
            </div>

            <? // Ссылка «Читать все отзывы» временно скрыта по просьбе.
               // Чтобы вернуть — убрать «false &&» в условии ниже. ?>
            <? if (false && $reviewsUrl !== ''): ?>
                <a class="reviews__all" href="<?= htmlspecialcharsbx($reviewsUrl) ?>"
                   target="_blank" rel="noopener nofollow">Читать все отзывы</a>
            <? endif ?>
        </div>
        <? endif ?>

        <div class="reviews__carousel" data-reviews>
            <button type="button" class="reviews__arrow reviews__arrow--prev" data-reviews-prev aria-label="Предыдущие отзывы">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#6B6B6B" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
            </button>

            <ul class="reviews__track" data-reviews-track>
                <? foreach ($arResult['ITEMS'] as $arItem):
                    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
                    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'));

                    // Оценка: по умолчанию 5, обрезаем в диапазон 1..5
                    $rating = (int)($arItem['PROPERTIES']['RATING']['VALUE'] ?? 5);
                    $rating = max(1, min(5, $rating ?: 5));

                    // Дата: «24 января» для текущего года, «8 ноября 2025» — для прошлых (как в макете).
                    // Сначала свойство «Дата отзыва» (заполняет контент-редактор),
                    // если пусто — старое поле «Активность с» (старые отзывы не ломаем).
                    $dateText = '';
                    $rawDate  = trim((string)($arItem['PROPERTIES']['DATE']['VALUE'] ?? ''));
                    if ($rawDate === '' && !empty($arItem['ACTIVE_FROM'])) {
                        $rawDate = (string)$arItem['ACTIVE_FROM'];
                    }
                    if ($rawDate !== '') {
                        $ts = MakeTimeStamp($rawDate) ?: strtotime($rawDate);
                        if ($ts) {
                            $dateText = FormatDate((int)date('Y', $ts) === $currentYear ? 'j F' : 'j F Y', $ts);
                        }
                    }

                    // Фото: превью 100×100 в карточке, оригинал — в лайтбокс
                    $photos = [];
                    foreach ((array)($arItem['PROPERTIES']['PHOTOS']['VALUE'] ?? []) as $fileId) {
                        $file = CFile::GetFileArray($fileId);
                        if (!$file) continue;
                        $thumb = CFile::ResizeImageGet(
                            $fileId,
                            ['width' => 200, 'height' => 200],
                            BX_RESIZE_IMAGE_EXACT,
                            true
                        );
                        $photos[] = [
                            'FULL'  => $file['SRC'],
                            'THUMB' => $thumb['src'] ?? $file['SRC'],
                        ];
                    }

                    // Текст: если в админке выбран «текст» — экранируем, если HTML — отдаём как есть.
                    // Отзывы копируют из Яндекса, и в базу нередко попадают уже закодированные символы
                    // («5&#43;» вместо «5+»). Сначала раскодируем их, иначе на странице виден сам код.
                    $text = (string)$arItem['PREVIEW_TEXT'];
                    $isHtml = ($arItem['PREVIEW_TEXT_TYPE'] ?? 'text') === 'html';
                    if (!$isHtml) {
                        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        // В «текстовом» режиме в базе иногда лежат литеральные теги <br>
                        // (скопировали из Яндекса вместе с разметкой). Без этого после
                        // экранирования на странице виден сам текст «<br>». Превращаем их
                        // в переносы строк — ниже nl2br() сделает из них настоящие <br>.
                        $text = preg_replace('~<br\s*/?>~i', "\n", $text);
                    }
                    $textHtml = $isHtml ? $text : nl2br(htmlspecialcharsbx($text));
                    $itemId   = (int)$arItem['ID'];
                ?>
                <li class="reviews__slide">
                    <article class="review-card" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
                        <header class="review-card__head">
                            <p class="review-card__author"><?= htmlspecialcharsbx($arItem['NAME']) ?></p>
                            <? if ($dateText !== ''): ?>
                                <p class="review-card__date"><?= htmlspecialcharsbx($dateText) ?></p>
                            <? endif ?>
                            <span class="stars" role="img" aria-label="Оценка <?= $rating ?> из 5">
                                <?= $renderStars($rating, 20) ?>
                            </span>
                        </header>

                        <div class="review-card__body">
                            <? if ($photos): ?>
                            <? // Больше трёх фото — ряд прокручивается, JS вешает стрелку (см. скрипт ниже) ?>
                            <div class="review-card__photos-wrap<?= count($photos) > 3 ? ' has-more' : '' ?>">
                                <div class="review-card__photos">
                                    <? foreach ($photos as $photo): ?>
                                        <a class="review-card__photo"
                                           href="<?= htmlspecialcharsbx($photo['FULL']) ?>"
                                           data-fancybox="review-<?= $itemId ?>">
                                            <img src="<?= htmlspecialcharsbx($photo['THUMB']) ?>" width="100" height="100" loading="lazy"
                                                 alt="Фото к отзыву: <?= htmlspecialcharsbx($arItem['NAME']) ?>">
                                        </a>
                                    <? endforeach ?>
                                </div>
                                <? if (count($photos) > 3): ?>
                                <button type="button" class="review-card__photos-more" aria-label="Показать ещё фото">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#1F1F1F" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M13 6l6 6-6 6"/>
                                    </svg>
                                </button>
                                <? endif ?>
                            </div>
                            <? endif ?>

                            <div class="review-card__text"><?= $textHtml ?></div>
                        </div>

                        <? // Кнопку показывает JS — только там, где текст реально не влез в карточку ?>
                        <button type="button" class="review-card__more"
                                data-fancybox="review-more-<?= $itemId ?>"
                                data-src="#review-full-<?= $itemId ?>"
                                data-type="inline">Читать весь отзыв</button>
                    </article>

                    <? // Полная версия отзыва — скрыта, открывается в попапе (Fancybox inline) ?>
                    <div class="review-modal" id="review-full-<?= $itemId ?>" style="display:none">
                        <? // Крестик внутри окна (по макету). data-fancybox-close закрывает попап. ?>
                        <button type="button" class="review-modal__x" data-fancybox-close aria-label="Закрыть">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                        <header class="review-card__head">
                            <p class="review-card__author"><?= htmlspecialcharsbx($arItem['NAME']) ?></p>
                            <? if ($dateText !== ''): ?>
                                <p class="review-card__date"><?= htmlspecialcharsbx($dateText) ?></p>
                            <? endif ?>
                            <span class="stars" role="img" aria-label="Оценка <?= $rating ?> из 5">
                                <?= $renderStars($rating, 20) ?>
                            </span>
                        </header>
                        <? if ($photos): ?>
                        <div class="review-modal__photos">
                            <? foreach ($photos as $photo): ?>
                                <a class="review-card__photo"
                                   href="<?= htmlspecialcharsbx($photo['FULL']) ?>"
                                   data-fancybox="review-full-gallery-<?= $itemId ?>">
                                    <img src="<?= htmlspecialcharsbx($photo['THUMB']) ?>" width="100" height="100" loading="lazy"
                                         alt="Фото к отзыву: <?= htmlspecialcharsbx($arItem['NAME']) ?>">
                                </a>
                            <? endforeach ?>
                        </div>
                        <? endif ?>
                        <div class="review-modal__text"><?= $textHtml ?></div>
                        <button type="button" class="review-modal__close">Закрыть</button>
                    </div>
                </li>
                <? endforeach ?>
            </ul>

            <button type="button" class="reviews__arrow reviews__arrow--next" data-reviews-next aria-label="Следующие отзывы">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#1F1F1F" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6"/>
                </svg>
            </button>

            <div class="reviews__dots" data-reviews-dots aria-hidden="true"></div>
        </div>
    </div>
</section>

<script>
(function () {
    var root = document.querySelector('[data-reviews]');
    if (!root) return;

    var track = root.querySelector('[data-reviews-track]');
    var prev  = root.querySelector('[data-reviews-prev]');
    var next  = root.querySelector('[data-reviews-next]');
    var dots  = root.querySelector('[data-reviews-dots]');
    var slides = track.querySelectorAll('.reviews__slide');
    if (!slides.length) return;

    /* Шаг прокрутки = ширина карточки + отступ между карточками */
    function step() {
        if (slides.length < 2) return slides[0].offsetWidth;
        return slides[1].offsetLeft - slides[0].offsetLeft;
    }

    /* Точки (нужны на смартфоне, где карточка одна в кадре) */
    slides.forEach(function (_, i) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'reviews__dot';
        dot.setAttribute('aria-label', 'Отзыв ' + (i + 1));
        dot.addEventListener('click', function () {
            track.scrollTo({ left: slides[i].offsetLeft - track.offsetLeft, behavior: 'smooth' });
        });
        dots.appendChild(dot);
    });
    var dotList = dots.querySelectorAll('.reviews__dot');

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
    });
    window.addEventListener('resize', sync);
    sync();

    /* «Читать весь отзыв» — показываем только там, где текст реально обрезан
       (карточки фиксированной высоты, см. .review-card в styles.css) */
    function syncOverflow() {
        track.querySelectorAll('.review-card').forEach(function (card) {
            var text = card.querySelector('.review-card__text');
            if (!text) return;
            card.classList.toggle('is-overflowing', text.scrollHeight > text.clientHeight + 1);
        });
    }
    syncOverflow();
    window.addEventListener('resize', syncOverflow);
    window.addEventListener('load', syncOverflow); /* после загрузки шрифтов высота меняется */

    /* Стрелка листания фото (когда их больше трёх): вперёд, с конца — обратно к началу */
    root.querySelectorAll('.review-card__photos-wrap.has-more').forEach(function (wrap) {
        var row = wrap.querySelector('.review-card__photos');
        var btn = wrap.querySelector('.review-card__photos-more');
        if (!row || !btn) return;

        function atEnd() { return row.scrollLeft >= row.scrollWidth - row.clientWidth - 1; }
        btn.addEventListener('click', function () {
            if (atEnd()) row.scrollTo({ left: 0, behavior: 'smooth' });
            else row.scrollBy({ left: 110, behavior: 'smooth' }); /* шаг = фото 100px + зазор */
        });
        row.addEventListener('scroll', function () {
            btn.classList.toggle('is-end', atEnd());
        }, { passive: true });
    });

    /* Кнопка «Закрыть» внутри попапа отзыва */
    document.addEventListener('click', function (e) {
        if (e.target.closest('.review-modal__close') && window.Fancybox) Fancybox.close();
    });

    /* Лайтбокс фотографий отзыва */
    if (window.Fancybox) {
        Fancybox.bind('[data-fancybox^="review-"]', { Thumbs: false });
    } else {
        window.addEventListener('load', function () {
            if (window.Fancybox) Fancybox.bind('[data-fancybox^="review-"]', { Thumbs: false });
        });
    }
})();
</script>
