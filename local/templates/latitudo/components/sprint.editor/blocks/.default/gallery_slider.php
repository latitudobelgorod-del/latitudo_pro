<?php
/**
 * Блок «Галерея в виде слайдера» — публичный вывод.
 *
 * Свой блок редактора (local/admin/sprint.editor/blocks/gallery_slider) — копия штатной
 * «Галереи изображений» по админке, но на сайте рисуется слайдером в стиле сайта:
 * скруглённые углы, круглые белые стрелки по бокам, подпись поверх фото снизу —
 * как в блоке «Реализованные проекты» (Figma 537:23083).
 *
 * Штатная «Галерея изображений» осталась на месте и работает как раньше: контент-менеджер
 * выбирает нужный блок сам.
 *
 * Подпись слайда — поле «Описание» изображения (desc в данных блока).
 * Клик по фото открывает его в полном размере (Fancybox уже подключён шаблоном).
 *
 * @var array $block
 * @var SprintEditorBlocksComponent $this
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$images = \Sprint\Editor\Blocks\Gallery::getImages(
    $block,
    // Что показываем на странице: колонка редактора узкая, но берём с запасом под retina.
    ['width' => 1200, 'height' => 900, 'exact' => 0],
    // Что открывается по клику.
    ['width' => 1920, 'height' => 1440, 'exact' => 0]
);
if (!$images) {
    return;
}

$hasSlider = count($images) > 1;
// Своя группа для Fancybox на каждый блок: иначе несколько галерей на странице
// склеятся в одну ленту «вперёд/назад».
$groupId = 'editor-gallery-' . substr(md5(implode('|', array_column($images, 'SRC'))), 0, 8);
?>
<div class="editor-gallery<?= $hasSlider ? ' swiper' : '' ?>">
    <div class="<?= $hasSlider ? 'swiper-wrapper' : 'editor-gallery__single' ?>">
        <?php foreach ($images as $image):
            $desc   = trim((string)($image['DESCRIPTION'] ?? ''));
            $detail = (string)($image['DETAIL_SRC'] ?? $image['SRC']);
            ?>
            <div class="<?= $hasSlider ? 'swiper-slide' : 'editor-gallery__slide' ?>">
                <a class="editor-gallery__zoom" href="<?= htmlspecialcharsbx($detail) ?>"
                   data-fancybox="<?= $groupId ?>"
                   <?php if ($desc !== ''): ?>data-caption="<?= htmlspecialcharsbx($desc) ?>"<?php endif; ?>>
                    <img class="editor-gallery__img" src="<?= htmlspecialcharsbx((string)$image['SRC']) ?>"
                         alt="<?= htmlspecialcharsbx($desc) ?>" loading="lazy">
                </a>
                <?php if ($desc !== ''): ?>
                <span class="editor-gallery__caption"><?= htmlspecialcharsbx($desc) ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($hasSlider): ?>
    <button type="button" class="editor-gallery__nav editor-gallery__nav--prev" aria-label="Предыдущее фото">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
    </button>
    <button type="button" class="editor-gallery__nav editor-gallery__nav--next" aria-label="Следующее фото">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
    </button>
    <?php endif; ?>
</div>
<?php
// Инициализация одна на страницу, сколько бы галерей на ней ни было.
static $initPrinted = false;
if ($initPrinted) {
    return;
}
$initPrinted = true;
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* Swiper и Fancybox грузятся с defer — к DOMContentLoaded уже готовы. */
    if (window.Swiper) {
        document.querySelectorAll('.editor-gallery.swiper').forEach(function (el) {
            if (el.swiper) return;
            new Swiper(el, {
                loop: true,
                navigation: {
                    prevEl: el.querySelector('.editor-gallery__nav--prev'),
                    nextEl: el.querySelector('.editor-gallery__nav--next')
                }
            });
        });
    }
    if (window.Fancybox) {
        /* groupAll: false — каждая галерея сама по себе, см. группу в data-fancybox. */
        Fancybox.bind('[data-fancybox^="editor-gallery-"]', { groupAll: false });
    }
});
</script>
