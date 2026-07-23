<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */
/** @var array $arParams */
/** @var CMain $APPLICATION */
/** @var CBitrixComponentTemplate $this */

if (empty($arResult["ITEMS"])) return;

$arItem = reset($arResult["ITEMS"]);

// latitudoStoreText() — единая «распаковка» текстовых полей филиала: снимает лишнее
// экранирование, которое навешивает компонент news.list, экранирует ровно один раз
// и возвращает переносы строк (<br> и Enter из админки). См. include/region.php.
// Результат уже безопасен — повторно htmlspecialcharsbx() к нему применять НЕЛЬЗЯ.
$cOrg       = latitudoStoreText($arItem["PROPERTIES"]["ORGANIZATION"]["VALUE"]      ?? '');
$cOffice    = latitudoStoreText($arItem["PROPERTIES"]["ADDRESS"]["VALUE"]           ?? '');
$cWarehouse = latitudoStoreText($arItem["PROPERTIES"]["ADDRESS_WAREHOUSE"]["VALUE"] ?? '');
$cPhone     = (string)($arItem["PROPERTIES"]["PHONE"]["VALUE"]      ?? '');
$cEmail     = (string)($arItem["PROPERTIES"]["EMAIL"]["VALUE"]      ?? '');
$cHours     = latitudoStoreText($arItem["PROPERTIES"]["WORK_HOURS"]["VALUE"] ?? '');

// Карта. Поле MAP_EMBED («Embed-ссылка карты») содержит либо готовый <iframe> Яндекс-
// конструктора, либо просто ссылку. news.list отдаёт значение ЭКРАНИРОВАННЫМ
// (&lt;iframe…&gt;), поэтому сперва распаковываем — иначе «<iframe» не распознаётся.
$cMapRaw = html_entity_decode(
    trim((string)($arItem["PROPERTIES"]["MAP_EMBED"]["VALUE"] ?? '')),
    ENT_QUOTES | ENT_HTML5,
    'UTF-8'
);
$cMapHtml = '';
if ($cMapRaw !== '') {
    // Из вставленного <iframe> берём только src; если это просто ссылка — она и есть src.
    $mapSrc = preg_match('~src=["\']([^"\']+)["\']~i', $cMapRaw, $m) ? $m[1] : $cMapRaw;
    // БЕЗОПАСНОСТЬ: пускаем только карты Яндекса и пересобираем iframe по своему шаблону —
    // произвольный HTML/скрипт из поля наружу не попадёт (ср. бейдж отзывов в reviews.php).
    if (preg_match('~^https://yandex\.ru/(map-widget|maps)/~i', $mapSrc)) {
        $cMapHtml = '<iframe class="contacts__map-frame" src="' . htmlspecialcharsbx($mapSrc)
            . '" width="100%" height="100%" frameborder="0" loading="lazy" allowfullscreen></iframe>';
    }
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
                        <div class="contacts__value contacts__value--strong"><?= $cHours ?></div>
                    </div>
                    <? endif ?>
                    <div class="contacts__item">
                        <div class="contacts__label">Мы в мессенджерах:</div>
                        <? // Одна кнопка вместо иконок: открывает единую форму заявки, где клиент
                           // выбирает мессенджер (как баннер «Есть вопросы?», см. feedback.php). ?>
                        <button type="button" class="contacts__messenger-btn js-request-form"
                                data-form-title="Написать в мессенджер">Написать в мессенджер</button>
                    </div>
                </div>

                <? // Кнопка из мобильного макета (537:39353) — на десктопе скрыта ?>
                <button type="button" class="contacts__cta js-request-form"
                        data-form-title="Оставить заявку на ДПК">Оставить заявку на ДПК</button>
            </div>

            <? if ($cMapHtml !== ''): ?>
            <div class="contacts__map"><?= $cMapHtml ?></div>
            <? endif ?>
        </div>
    </div>
</section>
