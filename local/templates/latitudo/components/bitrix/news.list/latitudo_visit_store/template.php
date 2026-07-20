<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
/** @var CMain $APPLICATION */
/** @var CBitrixComponentTemplate $this */

if (empty($arResult["ITEMS"])) return;

$arItem = reset($arResult["ITEMS"]);

// Предложный падеж города: функция из region.php.
// Код филиала берём из latitudoCurrentRegionCode() (msk/krd/…), а НЕ из свойства SUBDOMAIN:
// на проде в SUBDOMAIN лежит полный домен (krd.latitudo.pro), такого ключа в карте падежей нет →
// фоллбэк возвращал NAME и заголовок читался «Посетите магазин в Краснодар».
// Блок и так фильтруется по '=CODE' => latitudoCurrentRegionCode() (см. footer.php), так что
// это тот же самый филиал — источник кода один на весь сайт.
$vsCode   = function_exists('latitudoCurrentRegionCode') ? latitudoCurrentRegionCode() : '';
$vsCityIn = function_exists('latitudoRegionPrepositional')
    ? htmlspecialcharsbx(latitudoRegionPrepositional($vsCode, $arItem["NAME"]))
    : htmlspecialcharsbx($arItem["NAME"]);

// Галерея: file ID → URL
$vsGalleryUrls = [];
foreach ((array)($arItem["PROPERTIES"]["GALLERY"]["VALUE"] ?? []) as $fid) {
    if (empty($fid)) continue;
    $url = CFile::GetPath($fid);
    if ($url) $vsGalleryUrls[] = $url;
}

// Менеджеры: три параллельных массива по индексу
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
<section class="section" id="visit-store">
    <div class="container">
        <div class="visit-store">
            <div class="visit-store__head">
                <h2 class="visit-store__title">Посетите магазин в <?= $vsCityIn ?></h2>
                <? $APPLICATION->IncludeFile(
                    "/include/visit-store-intro.php",
                    Array(),
                    Array("MODE" => "html", "NAME" => "Блок «Посетите магазин» — описание")
                ); ?>
            </div>

            <? // Десктоп — 3 фото в ряд; смартфон — карусель с точками (макет 537:39261) ?>
            <? if (!empty($vsGalleryUrls)): ?>
            <div class="visit-store__gallery" data-carousel>
                <div class="visit-store__gallery-track" data-carousel-track>
                    <? foreach ($vsGalleryUrls as $i => $src): ?>
                    <div class="visit-store__gallery-item">
                        <img src="<?= htmlspecialcharsbx($src) ?>"
                             alt="Магазин Latitudo в <?= $vsCityIn ?> — фото <?= $i + 1 ?>"
                             loading="lazy">
                    </div>
                    <? endforeach ?>
                </div>
                <div class="carousel-dots" data-carousel-dots aria-hidden="true"></div>
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
    </div>
</section>
