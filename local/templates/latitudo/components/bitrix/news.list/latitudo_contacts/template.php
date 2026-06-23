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
    ['icon' => 'vk.svg',       'label' => 'VK',       'href' => '#'],
    ['icon' => 'phone.svg',    'label' => 'Телефон',  'href' => $cPhoneHref],
];
?>
<section class="section" id="contacts">
    <div class="container">
        <div class="contacts">
            <div class="contacts__card">
                <div class="contacts__card-icon">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="48" height="48" rx="12" fill="#E85A1B"/>
                        <path d="M24 12C18.477 12 14 16.477 14 22C14 29.5 24 38 24 38C24 38 34 29.5 34 22C34 16.477 29.523 12 24 12ZM24 26C21.791 26 20 24.209 20 22C20 19.791 21.791 18 24 18C26.209 18 28 19.791 28 22C28 24.209 26.209 26 24 26Z" fill="white"/>
                    </svg>
                </div>

                <h2 class="contacts__title">Контакты</h2>

                <? if ($cOrg !== ''): ?>
                <div class="contacts__item">
                    <div class="contacts__label">Организация</div>
                    <div class="contacts__value contacts__value--strong"><?= $cOrg ?></div>
                </div>
                <? endif ?>

                <div class="contacts__row">
                    <? if ($cOffice !== ''): ?>
                    <div class="contacts__item">
                        <div class="contacts__label">Адрес офиса</div>
                        <div class="contacts__value"><?= $cOffice ?></div>
                    </div>
                    <? endif ?>
                    <? if ($cWarehouse !== ''): ?>
                    <div class="contacts__item">
                        <div class="contacts__label">Адрес склада</div>
                        <div class="contacts__value"><?= $cWarehouse ?></div>
                    </div>
                    <? endif ?>
                </div>

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

                <div class="contacts__row">
                    <? if ($cHours !== ''): ?>
                    <div class="contacts__item">
                        <div class="contacts__label">График работы</div>
                        <div class="contacts__value"><?= nl2br($cHours) ?></div>
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
            </div>

            <? if ($cMapHtml !== ''): ?>
            <div class="contacts__map"><?= $cMapHtml ?></div>
            <? endif ?>
        </div>
    </div>
</section>
