<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponentTemplate $this */
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
        <button type="button" class="filter-pill is-active" data-filter="all">Все</button>
        <? foreach ($apps as $slug => $label): ?>
            <button type="button" class="filter-pill" data-filter="<?= htmlspecialcharsbx($slug) ?>"><?= htmlspecialcharsbx($label) ?></button>
        <? endforeach ?>
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
            $img = $arItem["PREVIEW_PICTURE"]["SRC"] ?? '';
        ?>
        <figure class="project-card" data-app="<?= htmlspecialcharsbx(implode(' ', $slugs)) ?>" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
            <div class="project-card__media">
                <? if ($img): ?>
                    <img class="project-card__img" src="<?= $img ?>" alt="<?= htmlspecialcharsbx($arItem["NAME"]) ?>" loading="lazy">
                <? else: ?>
                    <span class="project-card__placeholder" aria-hidden="true"></span>
                <? endif ?>
            </div>
            <figcaption class="project-card__title"><?= htmlspecialcharsbx($arItem["NAME"]) ?></figcaption>
            <button type="button" class="project-card__more" aria-label="Подробнее">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M7 17 17 7M9 7h8v8"/></svg>
            </button>
        </figure>
        <? endforeach ?>
    </div>
</div>

<script>
(function () {
    var root = document.currentScript.previousElementSibling;
    if (!root || !root.classList.contains('projects')) {
        root = document.querySelector('.projects');
    }
    if (!root) return;
    var pills = root.querySelectorAll('.projects__filter .filter-pill');
    var cards = root.querySelectorAll('.projects__grid .project-card');
    pills.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var f = btn.getAttribute('data-filter');
            pills.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
            cards.forEach(function (card) {
                var apps = (card.getAttribute('data-app') || '').split(' ');
                card.style.display = (f === 'all' || apps.indexOf(f) !== -1) ? '' : 'none';
            });
        });
    });
})();
</script>
