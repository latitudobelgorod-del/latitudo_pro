<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Баннер «Есть вопросы? Давайте общаться в мессенджерах» — в Figma блок называется
 * «Обратная связь» (компонент 537:19267 — десктоп, 537:39300 — смартфон).
 *
 * Сквозной блок: выводится в footer.php шаблона перед «Контактами» на всех страницах.
 * Кнопка «Написать в мессенджер» открывает попап со ссылками мессенджеров ТЕКУЩЕГО
 * филиала (свойства TELEGRAM/WHATSAPP/MAX инфоблока «Магазины/Регионы», см. region.php).
 * Пока у филиала не заполнена ни одна ссылка — кнопки нет, баннер остаётся визиткой.
 */

function latitudoShowFeedbackBanner(): void
{
    $store = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;

    // Только заполненные мессенджеры; порядок = порядок кнопок в попапе
    $messengers = array_filter([
        'telegram' => trim((string)($store['TELEGRAM'] ?? '')),
        'whatsapp' => trim((string)($store['WHATSAPP'] ?? '')),
        'max'      => trim((string)($store['MAX'] ?? '')),
    ]);

    $labels = ['telegram' => 'Telegram', 'whatsapp' => 'WhatsApp', 'max' => 'MAX'];
    ?>
    <section class="section feedback" id="feedback">
        <div class="container">
            <div class="feedback-banner">
                <img class="feedback-banner__logo" src="<?= SITE_TEMPLATE_PATH ?>/images/logo-dark.webp"
                     width="143" height="32" loading="lazy" alt="Latitudo — террасы, заборы, фасады">

                <div class="feedback-banner__content">
                    <h2 class="feedback-banner__title">Есть вопросы?</h2>
                    <p class="feedback-banner__subtitle">Давайте общаться в мессенджерах</p>

                    <? if ($messengers): ?>
                        <button type="button" class="feedback-banner__btn"
                                data-fancybox="feedback-messengers"
                                data-src="#feedback-messengers" data-type="inline">Написать в мессенджер</button>
                    <? endif ?>
                </div>

                <img class="feedback-banner__photo" src="<?= SITE_TEMPLATE_PATH ?>/images/feedback-manager.webp"
                     width="463" height="674" loading="lazy"
                     alt="Менеджер Latitudo с образцом террасной доски">
            </div>

            <? if ($messengers): ?>
                <? // Попап выбора мессенджера — скрыт, открывается Fancybox (inline) ?>
                <div class="feedback-modal" id="feedback-messengers" style="display:none">
                    <h3 class="feedback-modal__title">Напишите нам</h3>
                    <p class="feedback-modal__text">Выберите удобный мессенджер — ответим в рабочее время.</p>
                    <ul class="feedback-modal__list">
                        <? foreach ($messengers as $key => $url): ?>
                            <li>
                                <a class="feedback-modal__link feedback-modal__link--<?= $key ?>"
                                   href="<?= htmlspecialcharsbx($url) ?>" target="_blank" rel="noopener nofollow">
                                    <?= $labels[$key] ?>
                                </a>
                            </li>
                        <? endforeach ?>
                    </ul>
                    <button type="button" class="feedback-modal__close">Закрыть</button>
                </div>

                <script>
                (function () {
                    /* Fancybox 5 требует явной привязки (скрипт fancybox грузится с defer) */
                    function bind() { Fancybox.bind('[data-fancybox="feedback-messengers"]', { Thumbs: false }); }
                    if (window.Fancybox) bind();
                    else window.addEventListener('load', function () { if (window.Fancybox) bind(); });

                    document.addEventListener('click', function (e) {
                        if (e.target.closest('.feedback-modal__close') && window.Fancybox) Fancybox.close();
                    });
                })();
                </script>
            <? endif ?>
        </div>
    </section>
    <?
}
