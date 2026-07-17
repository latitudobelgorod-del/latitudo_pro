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

// Категории = РАЗДЕЛЫ инфоблока «Реализованные проекты» (заказчик убрал свойство
// «Применение»: проекты лежат в разделах с теми же названиями). Раздел = таб.
$sections = [];   // sectionId => name, в порядке дерева/SORT
$rsSec = CIBlockSection::GetList(
    ["left_margin" => "ASC"],
    ["IBLOCK_ID" => $arParams["IBLOCK_ID"], "ACTIVE" => "Y", "GLOBAL_ACTIVE" => "Y"],
    false,
    ["ID", "NAME"]
);
while ($s = $rsSec->Fetch()) {
    $sections[(int)$s["ID"]] = $s["NAME"];
}

// Раздел каждого проекта. news.list отдаёт IBLOCK_SECTION_ID; если вдруг пусто —
// добираем одним батч-запросом (belt-and-suspenders).
$itemSection = [];   // elementId => sectionId
$needFetch = [];
foreach ($arResult["ITEMS"] as $arItem) {
    $sid = (int)($arItem["IBLOCK_SECTION_ID"] ?? 0);
    if ($sid) $itemSection[(int)$arItem["ID"]] = $sid;
    else      $needFetch[] = (int)$arItem["ID"];
}
if ($needFetch && class_exists('\Bitrix\Iblock\SectionElementTable')) {
    $rsSE = \Bitrix\Iblock\SectionElementTable::getList([
        "filter" => ["=IBLOCK_ELEMENT_ID" => $needFetch],
        "select" => ["IBLOCK_ELEMENT_ID", "IBLOCK_SECTION_ID"],
    ]);
    while ($r = $rsSE->fetch()) {
        $eid = (int)$r["IBLOCK_ELEMENT_ID"];
        if (!isset($itemSection[$eid])) $itemSection[$eid] = (int)$r["IBLOCK_SECTION_ID"];
    }
}

// Вкладки рисуем ТОЛЬКО для разделов, где реально есть проекты: иначе активная вкладка
// спрячет все карточки и блок опустеет. Разделов с проектами нет → вкладок нет,
// показываем до 4 проектов (fallback в JS: applyFilter(null)).
$usedSections = [];
foreach ($itemSection as $sid) {
    if (isset($sections[$sid])) $usedSections[$sid] = true;
}
$tabs = array_filter($sections, fn($id) => isset($usedSections[$id]), ARRAY_FILTER_USE_KEY);
?>
<div class="projects">
    <? if (!empty($tabs)): ?>
    <div class="projects__filter">
        <? // Раунд 4: вкладок «Все» нет — по умолчанию активна первая (непустая) категория.
           $firstPill = true; ?>
        <? foreach ($tabs as $sid => $name): ?>
            <button type="button" class="filter-pill<?= $firstPill ? ' is-active' : '' ?>" data-filter="<?= (int)$sid ?>"><?= htmlspecialcharsbx($name) ?></button>
        <? $firstPill = false; endforeach ?>
    </div>
    <? endif ?>

    <div class="projects__grid">
        <? foreach ($arResult["ITEMS"] as $arItem):
            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"));

            // Раздел проекта -> data-app (по нему фильтруют вкладки).
            $secId = $itemSection[(int)$arItem["ID"]] ?? 0;

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
        <figure class="project-card" data-app="<?= $secId ?: '' ?>" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
            <div class="project-card__slider<?= $hasSlider ? ' swiper' : '' ?>">
                <? if (!empty($slides)): ?>
                <div class="<?= $hasSlider ? 'swiper-wrapper' : 'project-card__static' ?>">
                    <? foreach ($slides as $i => $src): ?>
                    <div class="<?= $hasSlider ? 'swiper-slide' : 'project-card__slide' ?>">
                        <img class="project-card__img" src="<?= htmlspecialcharsbx($src) ?>"
                             alt="<?= htmlspecialcharsbx($arItem["NAME"]) ?><?= $i ? ' — фото ' . ($i + 1) : '' ?>"
                             loading="lazy">
                        <? // Прозрачный оверлей-ссылка поверх фото: сама картинка остаётся прямым
                           // ребёнком слайда (заполняет карточку), а клик открывает Fancybox-галерею. ?>
                        <a class="project-card__zoom" href="<?= htmlspecialcharsbx($src) ?>"
                           data-fancybox="project-<?= (int)$arItem['ID'] ?>"
                           data-caption="<?= htmlspecialcharsbx($arItem["NAME"]) ?>"
                           aria-label="Открыть фото"></a>
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

    /* --- Фильтр по разделам инфоблока (data-app = ID раздела) --- */
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

    /* --- Клик по фото → лайтбокс-галерея объекта (Fancybox, как в каталоге) ---
       Группа своя у каждой карточки (project-<ID>) — листаются только фото этого объекта.
       Fancybox подключён в header.php (defer). */
    function bindFancybox() {
        if (!window.Fancybox) return;
        Fancybox.bind('[data-fancybox^="project-"]', { groupAll: false });
    }
    if (window.Fancybox) bindFancybox();
    else window.addEventListener('load', bindFancybox);
})();
</script>
