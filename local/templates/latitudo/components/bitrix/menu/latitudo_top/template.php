<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<? if (!empty($arResult)): ?>
<ul class="nav">
    <? foreach ($arResult as $arItem):
        if ($arItem["PERMISSION"] < "R") continue; ?>
        <li class="nav__item<?= $arItem["SELECTED"] ? " nav__item--active" : "" ?>">
            <a href="<?= $arItem["LINK"] ?>" class="nav__link"><?= $arItem["TEXT"] ?></a>
        </li>
    <? endforeach ?>
</ul>
<? endif ?>
