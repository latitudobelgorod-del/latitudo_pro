<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?
$vsStore = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
if (!$vsStore) return;

$vsCityIn   = htmlspecialcharsbx($vsStore['CITY_IN']);
$vsDesc     = htmlspecialcharsbx($vsStore['DESCRIPTION']);

// Файловые ID → URL
$vsGalleryUrls = [];
foreach (($vsStore['GALLERY'] ?? []) as $fid) {
    $url = CFile::GetPath($fid);
    if ($url) $vsGalleryUrls[] = $url;
}

$vsManagers = [];
foreach (($vsStore['MANAGER_PHOTOS'] ?? []) as $i => $fid) {
    $url = CFile::GetPath($fid);
    if (!$url) continue;
    $vsManagers[] = [
        'photo'    => $url,
        'name'     => htmlspecialcharsbx($vsStore['MANAGER_NAMES'][$i] ?? ''),
        'position' => htmlspecialcharsbx($vsStore['MANAGER_POSITIONS'][$i] ?? ''),
    ];
}

if (empty($vsGalleryUrls) && empty($vsManagers)) return;
?>
<div class="visit-store">
    <div class="visit-store__head">
        <h2 class="visit-store__title">Посетите магазин в <?= $vsCityIn ?></h2>
        <? if ($vsDesc !== ''): ?>
        <p class="visit-store__subtitle"><?= $vsDesc ?></p>
        <? endif ?>
    </div>

    <? if (!empty($vsGalleryUrls)): ?>
    <div class="visit-store__gallery">
        <? foreach ($vsGalleryUrls as $i => $src): ?>
        <div class="visit-store__gallery-item">
            <img src="<?= htmlspecialcharsbx($src) ?>"
                 alt="Магазин Latitudo в <?= $vsCityIn ?> — фото <?= $i + 1 ?>"
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
</div>
