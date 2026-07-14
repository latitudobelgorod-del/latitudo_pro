<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
/** @var CMain $APPLICATION */
/** @var CBitrixComponentTemplate $this */

if (empty($arResult["ITEMS"])) return;

$arItem = reset($arResult["ITEMS"]);

$cOrg       = htmlspecialcharsbx((string)($arItem["PROPERTIES"]["ORGANIZATION"]["VALUE"]      ?? ''));
$cOffice    = htmlspecialcharsbx((string)($arItem["PROPERTIES"]["ADDRESS"]["VALUE"]           ?? ''));
$cWarehouse = htmlspecialcharsbx((string)($arItem["PROPERTIES"]["ADDRESS_WAREHOUSE"]["VALUE"] ?? ''));
$cPhone     = (string)($arItem["PROPERTIES"]["PHONE"]["VALUE"]      ?? '');
$cEmail     = (string)($arItem["PROPERTIES"]["EMAIL"]["VALUE"]      ?? '');
$cHours     = htmlspecialcharsbx((string)($arItem["PROPERTIES"]["WORK_HOURS"]["VALUE"] ?? ''));

$cMapRaw  = trim((string)($arItem["PROPERTIES"]["MAP_EMBED"]["VALUE"] ?? ''));
$cMapHtml = '';
if ($cMapRaw !== '') {
    $cMapHtml = (mb_stripos($cMapRaw, '<iframe') !== false)
        ? $cMapRaw
        : '<iframe src="' . htmlspecialcharsbx($cMapRaw) . '" width="100%" height="100%" frameborder="0" loading="lazy" allowfullscreen></iframe>';
}

$cPhoneHref = 'tel:' . preg_replace('/[^\d+]/', '', $cPhone);

// Мессенджеры — вшиты в шаблон, ссылки заменит заказчик
$cMessengers = [
    ['icon' => 'telegram.svg', 'label' => 'Telegram', 'href' => '#'],
    ['icon' => 'whatsapp.svg', 'label' => 'WhatsApp', 'href' => '#'],
    ['icon' => 'max.svg',      'label' => 'Max',      'href' => '#'],
    ['icon' => 'phone.svg',    'label' => 'Телефон',  'href' => $cPhoneHref],
];
?>
<section class="section" id="contacts">
    <div class="container">
        <div class="contacts">
            <div class="contacts__card">
                <h2 class="contacts__title">Контакты</h2>

                <? if ($cOrg !== ''): ?>
                <div class="contacts__item">
                    <div class="contacts__label">Организация</div>
                    <div class="contacts__value contacts__value--strong"><?= $cOrg ?></div>
                </div>
                <? endif ?>

                <hr class="contacts__divider">

                <div class="contacts__row">
                    <? if ($cOffice !== ''): ?>
                    <div class="contacts__item">
                        <div class="contacts__label">Адрес офиса</div>
                        <div class="contacts__value contacts__value--strong"><?= $cOffice ?></div>
                    </div>
                    <? endif ?>
                    <? if ($cWarehouse !== ''): ?>
                    <div class="contacts__item">
                        <div class="contacts__label">Адрес склада</div>
                        <div class="contacts__value contacts__value--strong"><?= $cWarehouse ?></div>
                    </div>
                    <? endif ?>
                </div>

                <hr class="contacts__divider">

                <div class="contacts__row">
                    <? if ($cPhone !== ''): ?>
                    <div class="contacts__item">
                        <div class="contacts__label">Мобильный телефон</div>
                        <div class="contacts__value contacts__value--strong">
                            <a href="<?= htmlspecialcharsbx($cPhoneHref) ?>"><?= htmlspecialcharsbx($cPhone) ?></a>
                        </div>
                    </div>
                    <? endif ?>
                    <? if ($cEmail !== ''): ?>
                    <div class="contacts__item">
                        <div class="contacts__label">Почта (email)</div>
                        <div class="contacts__value contacts__value--strong">
                            <a href="mailto:<?= htmlspecialcharsbx($cEmail) ?>"><?= htmlspecialcharsbx($cEmail) ?></a>
                        </div>
                    </div>
                    <? endif ?>
                </div>

                <hr class="contacts__divider">

                <div class="contacts__row">
                    <? if ($cHours !== ''): ?>
                    <div class="contacts__item">
                        <div class="contacts__label">График работы:</div>
                        <div class="contacts__value contacts__value--strong"><?= nl2br($cHours) ?></div>
                    </div>
                    <? endif ?>
                    <div class="contacts__item">
                        <div class="contacts__label">Мы в мессенджерах:</div>
                        <div class="contacts__messengers">
                            <? foreach ($cMessengers as $m): ?>
                            <a class="contacts__messenger" href="<?= htmlspecialcharsbx($m['href']) ?>" aria-label="<?= $m['label'] ?>">
                                <img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/<?= $m['icon'] ?>" alt="<?= $m['label'] ?>" width="40" height="40">
                            </a>
                            <? endforeach ?>
                        </div>
                    </div>
                </div>

                <? // Кнопка из мобильного макета (537:39353) — на десктопе скрыта ?>
                <button type="button" class="contacts__cta" data-stub="request">Оставить заявку на ДПК</button>
            </div>

            <? if ($cMapHtml !== ''): ?>
            <div class="contacts__map"><?= $cMapHtml ?></div>
            <? endif ?>
        </div>
    </div>
</section>
