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
$cOrg       = latitudoStoreText($arItem["PROPERTIES"]["REGION_ORG"]["VALUE"]        ?? '');
$cOffice    = latitudoStoreText($arItem["PROPERTIES"]["REGION_ADDRESS"]["VALUE"]    ?? '');
$cWarehouse = latitudoStoreText($arItem["PROPERTIES"]["REGION_WAREHOUSE"]["VALUE"]  ?? '');
$cPhone     = (string)($arItem["PROPERTIES"]["REGION_PHONE"]["VALUE"]      ?? '');
$cEmail     = (string)($arItem["PROPERTIES"]["REGION_EMAIL"]["VALUE"]      ?? '');
$cHours     = latitudoStoreText($arItem["PROPERTIES"]["REGION_WORK_HOURS"]["VALUE"] ?? '');

// Карта. Два поля, в каждом — готовый <iframe> конструктора карт либо просто ссылка:
//   TWO_GIS_CONSTR_MAP («Схема проезда - 2ГИС») — заполнено → выводится карта 2ГИС;
//   MAP_EMBED («Embed-ссылка карты», Яндекс)   — выводится, если поле 2ГИС пустое.
// news.list отдаёт значения ЭКРАНИРОВАННЫМИ (&lt;iframe…&gt;), поэтому сперва распаковываем —
// иначе «<iframe» не распознаётся. Из <iframe> берём только src; просто ссылка и есть src.
$cMapSrc = static function (string $code) use ($arItem): string {
    $raw = html_entity_decode(
        trim((string)($arItem["PROPERTIES"][$code]["VALUE"] ?? '')),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    return preg_match('~src=["\']([^"\']+)["\']~i', $raw, $m) ? $m[1] : $raw;
};
// БЕЗОПАСНОСТЬ: из каждого поля пускаем только карты своего сервиса и пересобираем iframe
// по своему шаблону — произвольный HTML/скрипт из поля наружу не попадёт (ср. бейдж отзывов
// в reviews.php). Ссылка не того сервиса считается пустым полем.
$twoGisSrc = $cMapSrc("TWO_GIS_CONSTR_MAP");
$yandexSrc = $cMapSrc("MAP_EMBED");
$cMapHtml  = '';
if (preg_match('~^https://makemap\.2gis\.ru/widget\?~i', $twoGisSrc)) {
    // sandbox — ровно тот, что выдаёт сам конструктор 2ГИС
    $cMapHtml = '<iframe class="contacts__map-frame" src="' . htmlspecialcharsbx($twoGisSrc)
        . '" width="100%" height="100%" frameborder="0" loading="lazy"'
        . ' sandbox="allow-modals allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation-by-user-activation"></iframe>';
} elseif (preg_match('~^https://yandex\.ru/(map-widget|maps)/~i', $yandexSrc)) {
    $cMapHtml = '<iframe class="contacts__map-frame" src="' . htmlspecialcharsbx($yandexSrc)
        . '" width="100%" height="100%" frameborder="0" loading="lazy" allowfullscreen></iframe>';
}

$cPhoneHref = 'tel:' . preg_replace('/[^\d+]/', '', $cPhone);

// Вывод — через плейсхолдеры #REGION_*# (подставляются обработчиком по поддомену,
// см. region.php). Значения выше нужны только для проверки «пусто/не пусто»; на страницу
// идёт плейсхолдер — так значения корректны даже при кэшировании компонента.
if ($cOrg       !== '') $cOrg       = '#REGION_ORG#';
if ($cOffice    !== '') $cOffice    = '#REGION_ADDRESS#';
if ($cWarehouse !== '') $cWarehouse = '#REGION_WAREHOUSE#';
if ($cHours     !== '') $cHours     = '#REGION_WORK_HOURS#';
if ($cEmail     !== '') $cEmail     = '#REGION_EMAIL#';
if ($cPhone     !== '') { $cPhone = '#REGION_PHONE#'; $cPhoneHref = '#REGION_PHONE_HREF#'; }

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

                <? // Кнопка «Оставить заявку на ДПК» из мобильного макета (537:39353) убрана
                   // по решению заказчика: на телефоне блок контактов заканчивается кнопкой
                   // «Написать в мессенджер», заявку клиент оставляет в блоке обратной связи. ?>
            </div>

            <? if ($cMapHtml !== ''): ?>
            <div class="contacts__map"><?= $cMapHtml ?></div>
            <? endif ?>
        </div>
    </div>
</section>
