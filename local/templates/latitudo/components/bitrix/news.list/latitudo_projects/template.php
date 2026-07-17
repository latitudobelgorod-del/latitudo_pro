<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Блок «Реализованные проекты» (Figma, раунд 4: 537:19165 — десктоп, 537:39060 — смартфон).
 * Карточка = слайдер фотографий объекта: круглые стрелки ← → по бокам фото,
 * название и описание — поверх затемнения (десктоп) / под фото (смартфон).
 *
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */
$this->setFrameMode(true);

// Все значения «Применение» (для кнопок фильтра) — XML_ID = слаг.
$apps = [];        // xmlId => текст
$valToSlug = [];   // текст => xmlId
$rsEnum = CIBlockPropertyEnum::GetList(["SORT" => "ASC"], ["IBLOCK_ID" => $arParams["IBLOCK_ID"], "CODE" => "APPLICATION"]);
while ($e = $rsEnum->Fetch()) {
    $apps[$e["XML_ID"]] = $e["VALUE"];
    $valToSlug[$e["VALUE"]] = $e["XML_ID"];
}
?>
<div class="projects">
    <? if (!empty($apps)): ?>
    <div class="projects__filter">
        <? // Раунд 4: вкладок «Все» нет — по умолчанию активна первая категория.
           $firstPill = true; ?>
        <? foreach ($apps as $slug => $label): ?>
            <button type="button" class="filter-pill<?= $firstPill ? ' is-active' : '' ?>" data-filter="<?= htmlspecialcharsbx($slug) ?>"><?= htmlspecialcharsbx($label) ?></button>
        <? $firstPill = false; endforeach ?>
    </div>
    <? endif ?>

    <div class="projects__grid">
        <? foreach ($arResult["ITEMS"] as $arItem):
            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"));

            // слаги «Применение» этого проекта -> data-app
            $slugs = [];
            if (!empty($arItem["PROPERTIES"]["APPLICATION"]["VALUE"])) {
                foreach ((array)$arItem["PROPERTIES"]["APPLICATION"]["VALUE"] as $v) {
                    if (isset($valToSlug[$v])) $slugs[] = $valToSlug[$v];
                }
            }

            // Слайды карточки: превью + фотографии галереи объекта (свойство GALLERY)
            $slides = [];
            if (!empty($arItem["PREVIEW_PICTURE"]["SRC"])) {
                $slides[] = $arItem["PREVIEW_PICTURE"]["SRC"];
            }
            foreach ((array)($arItem["PROPERTIES"]["GALLERY"]["VALUE"] ?? []) as $fid) {
                if (empty($fid)) continue;
                $url = CFile::GetPath($fid);
                if ($url) $slides[] = $url;
            }
            $slides = array_values(array_unique($slides));
            $hasSlider = count($slides) > 1;   // одна фотография — стрелки не нужны
        ?>
        <figure class="project-card" data-app="<?= htmlspecialcharsbx(implode(' ', $slugs)) ?>" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
            <div class="project-card__slider<?= $hasSlider ? ' swiper' : '' ?>">
                <? if (!empty($slides)): ?>
                <div class="<?= $hasSlider ? 'swiper-wrapper' : 'project-card__static' ?>">
                    <? foreach ($slides as $i => $src): ?>
                    <div class="<?= $hasSlider ? 'swiper-slide' : 'project-card__slide' ?>">
                        <img class="project-card__img" src="<?= htmlspecialcharsbx($src) ?>"
                             alt="<?= htmlspecialcharsbx($arItem["NAME"]) ?><?= $i ? ' — фото ' . ($i + 1) : '' ?>"
                             loading="lazy">
                    </div>
                    <? endforeach ?>
                </div>
                <? else: ?>
                <span class="project-card__placeholder" aria-hidden="true"></span>
                <? endif ?>

                <? if ($hasSlider): ?>
                <button type="button" class="project-card__nav project-card__nav--prev" aria-label="Предыдущее фото">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 12H5"/><path d="m11 6-6 6 6 6"/>
                    </svg>
                </button>
                <button type="button" class="project-card__nav project-card__nav--next" aria-label="Следующее фото">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 12h15"/><path d="m13 6 6 6-6 6"/>
                    </svg>
                </button>
                <? endif ?>
            </div>

            <figcaption class="project-card__body">
                <span class="project-card__title"><?= htmlspecialcharsbx($arItem["NAME"]) ?></span>
                <? if (!empty($arItem["PREVIEW_TEXT"])): ?>
                    <span class="project-card__desc"><?= htmlspecialcharsbx(strip_tags($arItem["PREVIEW_TEXT"])) ?></span>
                <? endif ?>
            </figcaption>
        </figure>
        <? endforeach ?>
    </div>
</div>

<script>
(function () {
    var root = document.querySelector('.projects');
    if (!root) return;

    /* --- Фильтр по «Применению» --- */
    var LIMIT = 4;   // раунд 4: показываем не больше 4 карточек выбранной категории
    var pills = root.querySelectorAll('.projects__filter .filter-pill');
    var cards = root.querySelectorAll('.projects__grid .project-card');

    // f === null → без фильтра (нет категорий): показать первые 4 карточки.
    function applyFilter(f) {
        var shown = 0;
        cards.forEach(function (card) {
            var apps = (card.getAttribute('data-app') || '').split(' ');
            var match = (f === null) || apps.indexOf(f) !== -1;
            if (match && shown < LIMIT) { card.style.display = ''; shown++; }
            else { card.style.display = 'none'; }
        });
        // Слайдеры, что были скрыты, инициализировались с нулевой шириной — пересчитываем.
        cards.forEach(function (card) {
            if (card.style.display === 'none') return;
            var el = card.querySelector('.project-card__slider.swiper');
            if (el && el.swiper) el.swiper.update();
        });
    }

    pills.forEach(function (btn) {
        btn.addEventListener('click', function () {
            pills.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
            applyFilter(btn.getAttribute('data-filter'));
        });
    });

    // Стартовое состояние: активная (первая) категория, либо первые 4 карточки.
    var activePill = root.querySelector('.projects__filter .filter-pill.is-active');
    applyFilter(activePill ? activePill.getAttribute('data-filter') : null);

    /* --- Слайдер фотографий внутри карточки (Swiper подключён в header.php) --- */
    function initSliders() {
        if (!window.Swiper) return;
        root.querySelectorAll('.project-card__slider.swiper').forEach(function (el) {
            if (el.swiper) return;
            new Swiper(el, {
                loop: true,
                navigation: {
                    prevEl: el.querySelector('.project-card__nav--prev'),
                    nextEl: el.querySelector('.project-card__nav--next')
                }
            });
        });
    }
    if (window.Swiper) initSliders();
    else window.addEventListener('load', initSliders);
})();
</script>
