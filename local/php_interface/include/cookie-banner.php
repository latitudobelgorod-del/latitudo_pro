<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Баннер о cookie (152-ФЗ ст. 9).
 * Figma (раунд 4): десктоп 537:19338 — полоса внизу экрана; смартфон 537:39883 —
 * карточка внизу поверх затемнения.
 *
 * ⚠️ С 2026-09-04 ЭТО УВЕДОМЛЕНИЕ, А НЕ ГЕЙТ. Раньше баннер решал, насколько подробно
 * Метрика собирает данные, и отказ выключал счётчик совсем. Заказчик попросил, чтобы
 * статистика уходила при любом выборе, — теперь счётчик грузится всегда и всегда
 * на полном уровне (см. header.php, там же записаны последствия и как вернуть гейт).
 *
 * ЧТО ОТСЮДА СЛЕДУЕТ ДЛЯ ЭТОГО ФАЙЛА:
 *   • Кука latitudo_cookie_consent по-прежнему ставится и по-прежнему читается
 *     (latitudoConsent(), поле b24_consent в заявке) — но на сбор больше не влияет.
 *     Её единственная работа сейчас — не показывать баннер повторно.
 *   • Кода запуска Метрики здесь больше нет: счётчик уже на странице к моменту клика.
 *   • Перезагрузки на «Отклонить» тоже нет: отключать нечего.
 *   • Текст переписан. В нём было «Аналитику включаем только с вашего согласия» —
 *     теперь это была бы прямая неправда, а неправда в тексте про cookie хуже,
 *     чем отсутствие гейта. Обещаний, которых код не выполняет, тут быть не должно.
 *
 * Срок хранения выбора — год для согласия и полгода для отказа: отказ переспросить
 * через полгода допустимо, согласие дольше держать удобнее посетителю.
 *
 * Домен куки: на проде с точкой (.latitudo.pro) — тогда выбор, сделанный на
 * msk.latitudo.pro, действует и на krd/vrn/belgorod/rnd, и баннер не всплывает
 * на каждом поддомене заново. На локалке атрибут domain не ставим.
 */
function latitudoShowCookieBanner(): void
{
    static $rendered = false;
    if ($rendered) {
        return; // баннер нужен на странице ровно один раз
    }
    $rendered = true;

    ?>
    <div class="cookie-banner" id="cookie-banner" role="dialog" aria-label="Использование cookie-файлов">
        <div class="cookie-banner__inner">
            <p class="cookie-banner__text">
                <span class="cookie-banner__title">Мы используем cookie-файлы</span>
                <span class="cookie-banner__desc">Часть из них нужна сайту для работы, остальные помогают нам понять,
                    как им пользуются, и сделать его удобнее. Продолжая пользоваться сайтом, вы соглашаетесь
                    с обработкой данных — подробности в <a class="cookie-banner__link js-doc-popup" href="/policy" data-src="#doc-policy">политике конфиденциальности</a>.</span>
            </p>
            <span class="cookie-banner__actions">
                <button type="button" class="cookie-banner__btn cookie-banner__btn--ghost" data-cookie-decline>Отклонить</button>
                <button type="button" class="cookie-banner__btn" data-cookie-accept>Принять</button>
            </span>
        </div>
    </div>

    <script>
    (function () {
        var NAME    = 'latitudo_cookie_consent';
        var banner  = document.getElementById('cookie-banner');
        if (!banner) return;

        function consentValue() {
            var m = document.cookie.match(/(?:^|;\s*)latitudo_cookie_consent=([01])/);
            return m ? m[1] : null;
        }

        /* Домен для cookie: на поддоменах прода — общий .latitudo.pro (один выбор
           на все 5 городов). На локалке/ином домене атрибут domain не ставим вообще. */
        function domainAttr() {
            var host = location.hostname;
            var m = host.match(/([^.]+\.[^.]+)$/);
            if (!m || host.indexOf('.') === -1 || /^\d+(\.\d+){3}$/.test(host)) return '';
            return host === 'localhost' ? '' : '; domain=.' + m[1];
        }

        function remember(value, days) {
            document.cookie = NAME + '=' + value + '; path=/; max-age=' + (days * 24 * 60 * 60)
                + '; SameSite=Lax' + domainAttr();
        }

        /* Показываем баннер, только если выбора ещё не делали. Разметка есть всегда,
           но скрыта в CSS — так согласившийся не видит мигания при загрузке. */
        if (consentValue() === null) banner.classList.add('is-visible');

        banner.addEventListener('click', function (e) {
            /* Обе кнопки делают теперь одно и то же: запоминают нажатие и убирают
               баннер. На сбор данных выбор не влияет (см. шапку файла и header.php),
               поэтому ни запуска счётчика, ни перезагрузки страницы здесь нет.
               Разные значения куки и разные сроки хранения оставлены сознательно:
               если гейт вернут, выбор посетителей уже будет записан и переспрашивать
               их заново не придётся. */
            if (e.target.closest('[data-cookie-accept]')) {
                remember('1', 365);
                banner.classList.remove('is-visible');
                return;
            }
            if (e.target.closest('[data-cookie-decline]')) {
                remember('0', 180);
                banner.classList.remove('is-visible');
            }
        });

        /* Передумать можно ссылкой «Настройки cookie» в подвале: сбрасываем выбор
           и показываем баннер снова. Отзыв согласия обязан быть не сложнее его выдачи. */
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.js-cookie-settings')) return;
            e.preventDefault();
            remember('', -1);
            banner.classList.add('is-visible');
            banner.scrollIntoView({ block: 'nearest' });
        });
    })();
    </script>
    <?
}
