<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

if (empty($arResult["ITEMS"])) return;

$arItem = reset($arResult["ITEMS"]); // один элемент — текущий город

// Галерея: массив file ID → URL
$vsGalleryUrls = [];
foreach ((array)($arItem["PROPERTIES"]["GALLERY"]["VALUE"] ?? []) as $fid) {
    if (empty($fid)) continue;
    $url = CFile::GetPath($fid);
    if ($url) $vsGalleryUrls[] = $url;
}

// Менеджеры: три параллельных массива, связанных по индексу
$vsPhotos    = (array)($arItem["PROPERTIES"]["MANAGER_PHOTO"]["VALUE"]    ?? []);
$vsNames     = (array)($arItem["PROPERTIES"]["MANAGER_NAME"]["VALUE"]     ?? []);
$vsPositions = (array)($arItem["PROPERTIES"]["MANAGER_POSITION"]["VALUE"] ?? []);

$vsManagers = [];
foreach ($vsPhotos as $i => $fid) {
    if (empty($fid)) continue;
    $url = CFile::GetPath($fid);
    if (!$url) continue;
    $vsManagers[] = [
        'photo'    => $url,
        'name'     => htmlspecialcharsbx($vsNames[$i]     ?? ''),
        'position' => htmlspecialcharsbx($vsPositions[$i] ?? ''),
    ];
}

if (empty($vsGalleryUrls) && empty($vsManagers)) return;
?>
<? if (!empty($vsGalleryUrls)): ?>
<div class="visit-store__gallery">
    <? foreach ($vsGalleryUrls as $i => $src): ?>
    <div class="visit-store__gallery-item">
        <img src="<?= htmlspecialcharsbx($src) ?>"
             alt="Магазин Latitudo — фото <?= $i + 1 ?>"
             loading="lazy">
    </div>
    <? endforeach ?>
</div>
<? endif ?>

<? if (!empty($vsManagers)): ?>
<div class="visit-store__managers">
    <? foreach ($vsManagers as $mgr): ?>
    <div class="visit-store__manager">
        <div class="visit-store__manager-photo">
            <img src="<?= htmlspecialcharsbx($mgr['photo']) ?>"
                 alt="<?= $mgr['name'] ?>"
                 loading="lazy">
        </div>
        <? if ($mgr['name'] !== ''): ?>
        <p class="visit-store__manager-name"><?= $mgr['name'] ?></p>
        <? endif ?>
        <? if ($mgr['position'] !== ''): ?>
        <p class="visit-store__manager-position"><?= $mgr['position'] ?></p>
        <? endif ?>
    </div>
    <? endforeach ?>
</div>
<? endif ?>
