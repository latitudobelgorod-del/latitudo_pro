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
            <? endif ?>
            <div class="catalog-card__body">
                <h3 class="catalog-card__title"><?= htmlspecialcharsbx($arSection['NAME']) ?></h3>
            </div>
            <span class="catalog-card__arrow" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M7 17 17 7M9 7h8v8"/></svg>
            </span>
        </a>
    <? endforeach ?>
</div>
