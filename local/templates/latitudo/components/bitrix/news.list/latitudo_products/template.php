<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?php
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponentTemplate $this */
$this->setFrameMode(true);

// Нет товаров — ничего не рисуем: блок должен схлопнуться, а не показывать заглушку.
if (empty($arResult['ITEMS'])) {
    return;
}

// Разметка карточки и ленты — в include/product-card.php (один экземпляр на весь сайт).
latitudoProductsGridOpen();
foreach ($arResult['ITEMS'] as $arItem) {
    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'));

    latitudoRenderProductCard(
        latitudoProductCardData($arItem, $arItem['PROPERTIES'] ?? [], $this->GetEditAreaId($arItem['ID']))
    );
}
latitudoProductsGridClose();

latitudoProductsSliderJs();
