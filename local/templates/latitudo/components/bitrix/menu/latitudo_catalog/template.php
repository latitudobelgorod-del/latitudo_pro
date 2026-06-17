<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<? if (!empty($arResult)): ?>
<ul class="dropdown__list">
    <? foreach ($arResult as $arItem):
        if ($arItem["PERMISSION"] < "R") continue; ?>
        <li class="dropdown__item">
            <a href="<?= $arItem["LINK"] ?>" class="dropdown__link"><?= $arItem["TEXT"] ?></a>
        </li>
    <? endforeach ?>
</ul>
<? endif ?>
