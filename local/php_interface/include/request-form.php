<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Единая «Форма заявки» (Figma, раунд 4: кадр «Форма заявки» 537:19344).
 * Одна модалка на весь сайт: её открывают ВСЕ кнопки-триггеры —
 * «Заказать расчёт» (hero и карточки товаров), «Оставить заявку на ДПК» (контакты),
 * «Написать в мессенджер» (баннер «Есть вопросы?»).
 *
 * Кнопка-триггер = любой элемент с атрибутами:
 *     data-fancybox="request-form" data-src="#request-form" data-type="inline"
 * (Fancybox уже подключён в header.php.)
 *
 * ── Как админу подключить настоящую веб-форму ──────────────────────────────
 * 1. Админка → Сервисы → Веб-формы → создать форму с полями:
 *    Имя, Телефон, Никнейм (Telegram), Предпочтительный мессенджер (Max/Telegram/WhatsApp).
 * 2. Узнать ID формы (число в списке веб-форм).
 * 3. Прописать его в local/php_interface/init.php:  define('LATITUDO_REQUEST_FORM_ID', <ID>);
 * Пока ID = 0 — показывается статичное превью формы по макету (данные не отправляются).
 */

function latitudoShowRequestForm(): void
{
    static $rendered = false;
    if ($rendered) {
        return; // модалка нужна на странице ровно один раз
    }
    $rendered = true;

    global $APPLICATION;

    $formId = defined('LATITUDO_REQUEST_FORM_ID') ? (int)LATITUDO_REQUEST_FORM_ID : 0;
    $formConnected = $formId > 0 && CModule::IncludeModule('form');
    ?>
    <div class="request-modal" id="request-form" style="display:none" role="dialog" aria-label="Форма заявки">
        <h3 class="request-modal__title">Заказать расчёт</h3>

        <? if ($formConnected): ?>
            <? // Настоящая веб-форма Битрикс — поля и обработку задаёт админ в админке.
            $APPLICATION->IncludeComponent(
                "bitrix:form.result.new",
                "",
                array(
                    "WEB_FORM_ID"            => $formId,
                    "SEF_MODE"               => "N",
                    "START_PARAM"            => "WEB_FORM_ID",
                    "CHAIN_ITEM_TEXT"        => "",
                    "CHAIN_ITEM_LINK"        => "",
                    "IGNORE_CUSTOM_TEMPLATE" => "N",
                    "USE_EXTENDED_ERRORS"    => "Y",
                    "CACHE_TYPE"             => "N",
                    "AJAX_MODE"              => "Y",
                    "AJAX_OPTION_JUMP"       => "N",
                    "AJAX_OPTION_STYLE"      => "Y",
                    "AJAX_OPTION_HISTORY"    => "N",
                ),
                false
            ); ?>
        <? else: ?>
            <? // Заглушка-превью по макету: форму ещё не подключил админ (LATITUDO_REQUEST_FORM_ID = 0).
               // Данные НЕ отправляются — по сабмиту показываем честную подсказку. ?>
            <form class="request-form" id="request-form-preview" novalidate>
                <div class="request-form__field">
                    <label class="request-form__label" for="rf-name">Имя <span class="request-form__req">*</span></label>
                    <input class="request-form__input" type="text" id="rf-name" name="name" placeholder="Иванов Иван">
                </div>
                <div class="request-form__field">
                    <label class="request-form__label" for="rf-phone">Номер телефона <span class="request-form__req">*</span></label>
                    <input class="request-form__input" type="tel" id="rf-phone" name="phone" placeholder="+7 (999) 999-99-99">
                </div>
                <div class="request-form__field">
                    <label class="request-form__label" for="rf-nick">Никнейм (для аккаунта Telegram)</label>
                    <input class="request-form__input" type="text" id="rf-nick" name="nickname" placeholder="@ivanov">
                </div>
                <div class="request-form__field">
                    <span class="request-form__label">Предпочтительный мессенджер</span>
                    <div class="request-form__radios">
                        <label class="request-form__radio"><input type="radio" name="messenger" value="max" checked> Max</label>
                        <label class="request-form__radio"><input type="radio" name="messenger" value="telegram"> Telegram</label>
                        <label class="request-form__radio"><input type="radio" name="messenger" value="whatsapp"> WhatsApp</label>
                    </div>
                </div>

                <p class="request-form__notice" id="rf-notice" hidden>
                    Онлайн-отправка скоро заработает. Пожалуйста, позвоните нам или напишите в мессенджер.
                </p>

                <button class="request-form__submit" type="submit">Отправить заявку</button>

                <p class="request-form__disclaimer">
                    Если вы оставили заявку и ожидаете ответ в Max или Telegram, проверьте и скорректируйте
                    настройки конфиденциальности, чтобы мы могли вам написать.
                </p>
            </form>
        <? endif ?>
    </div>

    <script>
    (function () {
        /* Открытие модалки по клику на любой триггер .js-request-form.
           Через Fancybox.show (не data-fancybox), иначе несколько одинаковых кнопок
           Fancybox склеивает в галерею с «вперёд/назад». Здесь — всегда одно окно. */
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.js-request-form');
            if (!trigger) return;
            e.preventDefault();
            if (!window.Fancybox) return; // fancybox грузится с defer — к клику уже готов
            Fancybox.show(
                [{ src: '#request-form', type: 'inline' }],
                { mainClass: 'fancybox-request', Thumbs: false }
            );
        });

        /* Заглушка-превью: честная подсказка вместо «отправки в никуда» */
        var preview = document.getElementById('request-form-preview');
        if (preview) {
            preview.addEventListener('submit', function (e) {
                e.preventDefault();
                var notice = document.getElementById('rf-notice');
                if (notice) notice.hidden = false;
            });
        }
    })();
    </script>
    <?
}
