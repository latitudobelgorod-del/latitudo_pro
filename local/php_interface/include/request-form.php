<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Единая «Форма заявки» на компоненте ядра bitrix:main.feedback (модуль main).
 * Одна модалка на весь сайт: её открывают ВСЕ кнопки-триггеры .js-request-form —
 * «Заказать расчёт» (hero и карточки товаров), «Оставить заявку на ДПК» (контакты),
 * «Написать в мессенджер» (баннер «Есть вопросы?»).
 *
 * Почему main.feedback, а не «Веб-формы»: модуль form не входит в редакцию «Старт».
 * main.feedback — часть ядра, шлёт заявку письмом через событие FEEDBACK_FORM.
 *
 * ── Отправка ──────────────────────────────────────────────────────────────────
 * Компонент несёт всего 3 поля: user_name → #AUTHOR#, user_email → #AUTHOR_EMAIL#,
 * MESSAGE → #TEXT#. Телефон/ник/мессенджер JS собирает в скрытое поле MESSAGE.
 * Модалка отправляется AJAX-ом (fetch POST на текущую страницу); успех ловим по
 * редиректу компонента на ?success=…, после чего показываем блок «Спасибо».
 *
 * ── Куда летят заявки и как настроить письмо ──────────────────────────────────
 * Адрес: константа LATITUDO_FEEDBACK_EMAIL (init.php).
 * Шаблон письма: событие FEEDBACK_FORM, создаётся tools/setup-feedback-mail.php.
 * ID шаблона у локалки и прода РАЗНЫЙ (разные базы), поэтому не хардкодим его в git —
 * код сам находит наш шаблон по метке LATITUDO_FEEDBACK_MARK (см. latitudoFeedbackMailId()).
 * Текст письма админ правит в Настройки → Настройки продукта → Почтовые события.
 */

// Метка нашего почтового шаблона (совпадает с tools/setup-feedback-mail.php).
const LATITUDO_FEEDBACK_MARK = '[LATITUDO_FEEDBACK]';

/**
 * ID почтового шаблона FEEDBACK_FORM.
 * Приоритет — явная константа LATITUDO_FEEDBACK_MAIL_ID (init.php), если задана.
 * Иначе ищем свой шаблон по метке в теле — так код переносим между локалкой и продом
 * без ручной правки ID. 0 = шаблон ещё не создан (запустите setup-feedback-mail.php).
 */
function latitudoFeedbackMailId(): int
{
    if (defined('LATITUDO_FEEDBACK_MAIL_ID') && (int)LATITUDO_FEEDBACK_MAIL_ID > 0) {
        return (int)LATITUDO_FEEDBACK_MAIL_ID;
    }

    static $id = null;
    if ($id !== null) {
        return $id;
    }

    $id = 0;
    $by = 'id'; $order = 'desc';
    $rs = CEventMessage::GetList($by, $order, ['TYPE_ID' => 'FEEDBACK_FORM', 'ACTIVE' => 'Y']);
    while ($m = $rs->Fetch()) {
        if (mb_strpos((string)$m['MESSAGE'], LATITUDO_FEEDBACK_MARK) !== false) {
            $id = (int)$m['ID'];
            break;
        }
    }
    return $id;
}

/**
 * Параметры компонента — единый источник правды.
 * Одинаковый набор при выводе формы и при обработке POST: иначе не совпадёт
 * PARAMS_HASH (md5 от параметров + имени шаблона), и компонент отвергнет заявку.
 */
function latitudoFeedbackParams(): array
{
    $mailId = latitudoFeedbackMailId();
    $email  = defined('LATITUDO_FEEDBACK_EMAIL') ? (string)LATITUDO_FEEDBACK_EMAIL : '';

    return [
        'USE_CAPTCHA'        => 'N', // капчи нет — вместо неё honeypot (см. шаблон)
        'OK_TEXT'            => 'Спасибо! Заявка отправлена — мы свяжемся с вами.',
        'EMAIL_TO'           => $email,
        'REQUIRED_FIELDS'    => ['NAME', 'MESSAGE'], // e-mail не обязателен (у клиента только телефон)
        'EVENT_MESSAGE_ID'   => $mailId > 0 ? [$mailId] : [],
        'COMPONENT_TEMPLATE' => 'latitudo',
    ];
}

/**
 * Вывод/обработка компонента формы. Вызывается и в модалке, и на POST текущей страницы.
 * Honeypot проверяем ДО компонента: если скрытое поле заполнено — это бот,
 * молча ничего не отправляем (компонент не запускаем, письма нет).
 */
function latitudoRenderFeedbackComponent(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['latitudo_hp'])) {
        return;
    }

    global $APPLICATION;
    $APPLICATION->IncludeComponent(
        'bitrix:main.feedback',
        'latitudo',
        latitudoFeedbackParams(),
        false
    );
}

function latitudoShowRequestForm(): void
{
    static $rendered = false;
    if ($rendered) {
        return; // модалка нужна на странице ровно один раз
    }
    $rendered = true;
    ?>
    <div class="request-modal" id="request-form" style="display:none" role="dialog" aria-label="Форма заявки">
        <h3 class="request-modal__title">Заказать расчёт</h3>

        <div class="request-modal__body">
            <? latitudoRenderFeedbackComponent(); ?>
        </div>

        <div class="request-modal__thanks" hidden>
            <p class="request-modal__thanks-title">Спасибо! Заявка отправлена</p>
            <p class="request-modal__thanks-text">Мы свяжемся с вами в ближайшее время.</p>
        </div>
    </div>

    <script>
    (function () {
        var modal = document.getElementById('request-form');
        if (!modal) return;

        var form   = modal.querySelector('.request-form');
        var body   = modal.querySelector('.request-modal__body');
        var thanks = modal.querySelector('.request-modal__thanks');

        /* Открытие модалки по клику на любой триггер .js-request-form.
           Через Fancybox.show (не data-fancybox), иначе несколько одинаковых кнопок
           Fancybox склеивает в галерею «вперёд/назад». Здесь — всегда одно окно. */
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.js-request-form');
            if (!trigger) return;
            e.preventDefault();
            if (!window.Fancybox) return; // fancybox грузится с defer — к клику уже готов
            resetModal();
            Fancybox.show(
                [{ src: '#request-form', type: 'inline' }],
                { mainClass: 'fancybox-request', Thumbs: false }
            );
        });

        if (!form) return;

        var notice = form.querySelector('.request-form__notice');
        var submit = form.querySelector('.request-form__submit');

        function showError(msg) {
            if (!notice) return;
            notice.textContent = msg;
            notice.hidden = false;
        }
        function hideError() { if (notice) notice.hidden = true; }

        function resetModal() {
            if (body)   body.hidden = false;
            if (thanks) thanks.hidden = true;
            hideError();
            if (submit) { submit.disabled = false; submit.textContent = 'Отправить заявку'; }
        }
        function showThanks() {
            if (body)   body.hidden = true;
            if (thanks) thanks.hidden = false;
        }

        function val(name) {
            var el = form.querySelector('[name="' + name + '"]');
            return el ? el.value.trim() : '';
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            hideError();

            var name  = val('user_name');
            var phone = val('rf_phone');
            if (name.length < 2) { showError('Пожалуйста, укажите имя.'); return; }
            if (phone.replace(/\D/g, '').length < 6) { showError('Пожалуйста, укажите номер телефона.'); return; }

            /* Компонент понимает только MESSAGE — сворачиваем в него телефон/мессенджер/ник. */
            var nick = val('rf_nick');
            var mEl  = form.querySelector('[name="rf_messenger"]:checked');
            var lines = ['Телефон: ' + phone];
            if (mEl && mEl.value) lines.push('Мессенджер: ' + mEl.value);
            if (nick)             lines.push('Ник (Telegram): ' + nick);
            var msgEl = form.querySelector('[name="MESSAGE"]');
            if (msgEl) msgEl.value = lines.join('\n');

            if (submit) { submit.disabled = true; submit.textContent = 'Отправляем…'; }

            /* POST на текущую страницу: модалка (а значит и компонент) есть на каждой
               странице, компонент обработает заявку и сделает LocalRedirect на ?success=…
               fetch пойдёт по редиректу — по наличию success в конечном URL и судим об успехе. */
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) {
                if (r.redirected && r.url.indexOf('success=') !== -1) {
                    showThanks();
                } else {
                    showError('Не удалось отправить заявку. Позвоните нам или напишите в мессенджер.');
                }
            })
            .catch(function () {
                showError('Ошибка сети. Попробуйте ещё раз чуть позже.');
            })
            .finally(function () {
                if (submit) { submit.disabled = false; submit.textContent = 'Отправить заявку'; }
            });
        });
    })();
    </script>
    <?
}
