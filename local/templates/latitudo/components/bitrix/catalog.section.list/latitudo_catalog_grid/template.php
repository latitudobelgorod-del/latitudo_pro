<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponentTemplate $this */
$this->setFrameMode(true);

if (empty($arResult['SECTIONS'])) {
    return;
}

$sectionEdit = CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "SECTION_EDIT");
?>
<div class="catalog-grid">
    <? foreach ($arResult['SECTIONS'] as $arSection):
        $this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], $sectionEdit);
    ?>
        <a href="<?= $arSection['SECTION_PAGE_URL'] ?>" class="catalog-card" id="<?= $this->GetEditAreaId($arSection['ID']) ?>">
            <? if (!empty($arSection['PICTURE']['SRC'])): ?>
                <img class="catalog-card__img" src="<?= $arSection['PICTURE']['SRC'] ?>" alt="<?= htmlspecialcharsbx($arSection['NAME']) ?>" loading="lazy">
            <? else: ?>
                <? // Раздел без фото — держим квадрат, иначе стрелка наезжает на заголовок ?>
                <span class="catalog-card__img catalog-card__img--empty" aria-hidden="true"></span>
            <? endif ?>
            <div class="catalog-card__body">
                <h3 class="catalog-card__title"><?= htmlspecialcharsbx($arSection['NAME']) ?></h3>
            </div>
            <? // Макет: обычное состояние — белый круг с диагональной стрелкой,
               // при наведении — тёмно-красный круг с прямой стрелкой (Figma IconButton) ?>
            <span class="icon-arrow" aria-hidden="true">
                <svg class="icon-arrow__diag" viewBox="0 0 24 24" width="22" height="22" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 17 17 7"/><path d="M8 7h9v9"/>
                </svg>
                <svg class="icon-arrow__right" viewBox="0 0 24 24" width="22" height="22" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 12h15"/><path d="m13 6 6 6-6 6"/>
                </svg>
            </span>
        </a>
    <? endforeach ?>
</div>
