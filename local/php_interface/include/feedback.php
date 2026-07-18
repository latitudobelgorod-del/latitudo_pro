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
    ?>
    <section class="section feedback" id="feedback">
        <div class="container">
            <div class="feedback-banner">
                <img class="feedback-banner__logo" src="<?= SITE_TEMPLATE_PATH ?>/images/logo-dark.webp"
                     width="143" height="32" loading="lazy" alt="Latitudo — террасы, заборы, фасады">

                <div class="feedback-banner__content">
                    <h2 class="feedback-banner__title">Есть вопросы?</h2>
                    <p class="feedback-banner__subtitle">Давайте общаться в мессенджерах</p>

                    <? // Открывает единую «Форму заявки» — в ней клиент выбирает мессенджер.
                       // См. local/php_interface/include/request-form.php ?>
                    <button type="button" class="feedback-banner__btn js-request-form"
                            data-form-title="Написать в мессенджер">Написать в мессенджер</button>
                </div>

                <img class="feedback-banner__photo" src="<?= SITE_TEMPLATE_PATH ?>/images/feedback-manager.webp"
                     width="463" height="674" loading="lazy"
                     alt="Менеджер Latitudo с образцом террасной доски">
            </div>
        </div>
    </section>
    <?
}
