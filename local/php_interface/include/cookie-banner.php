<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Баннер согласия на аналитические cookie (152-ФЗ ст. 9).
 * Figma (раунд 4): десктоп 537:19338 — полоса внизу экрана; смартфон 537:39883 —
 * карточка внизу поверх затемнения.
 *
 * Это НЕ уведомление, а гейт: он решает, НАСКОЛЬКО подробно Метрика собирает данные.
 * Решение исполняется сервером, в header.php по куке latitudo_cookie_consent —
 * здесь баннер только собирает выбор.
 *
 * Три состояния куки (полное описание уровней сбора — в header.php):
 *   нет значения — не спрашивали: баннер показываем, Метрика работает на БАЗОВОМ
 *                  уровне (визиты и цели, без вебвизора и карты кликов);
 *   1            — согласие: ПОЛНЫЙ сбор, с вебвизором. Сервер включит его на
 *                  следующей странице; на текущей вебвизор не поднять — повторный
 *                  ym(id,'init') с другими параметрами Метрика игнорирует;
 *   0            — отказ: счётчик не грузится нигде и никогда, пока не передумают.
 *
 * Отказ равнозначен согласию: обе кнопки закрывают баннер и запоминают выбор.
 * Кнопки-«обманки» (когда отказ прячут или он ничего не делает) — ровно то,
 * за что аудит и выписал замечание.
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

    // Счётчик поднимаем сразу после нажатия «Принять» только на боевом домене —
    // условие то же, что и в header.php, иначе локалка засоряла бы статистику.
    $isProd = strpos((string)($_SERVER['HTTP_HOST'] ?? ''), 'latitudo.pro') !== false;
    ?>
    <div class="cookie-banner" id="cookie-banner" role="dialog" aria-label="Использование cookie-файлов">
        <div class="cookie-banner__inner">
            <p class="cookie-banner__text">
                <span class="cookie-banner__title">Мы используем cookie-файлы</span>
                <span class="cookie-banner__desc">Часть из них нужна сайту для работы, остальные помогают нам понять,
                    как им пользуются, и сделать его удобнее. Аналитику включаем только с вашего согласия —
                    подробности в <a class="cookie-banner__link js-doc-popup" href="/policy" data-src="#doc-policy">политике конфиденциальности</a>.</span>
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
        var IS_PROD = <?= $isProd ? 'true' : 'false' ?>;
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

        /* Запуск Метрики после «Принять» — тот же код, что в header.php.
           Нужен ровно в одном случае: счётчика на странице НЕТ, а человек согласился.
           Так бывает, если он раньше отказывался, а теперь передумал через «Настройки
           cookie» в подвале. У тех, кто ничего не выбирал, счётчик уже работает на
           базовом уровне — window.ym определён, и мы выходим сразу: апгрейд до
           вебвизора на живой странице невозможен, Метрика игнорирует повторный init
           с другими параметрами. Полный уровень включится сервером со следующей страницы. */
        function startMetrika() {
            if (!IS_PROD || window.ym) return;
            (function (m, e, t, r, i, k, a) {
                m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
                m[i].l = 1 * new Date();
                for (var j = 0; j < document.scripts.length; j++) { if (document.scripts[j].src === r) { return; } }
                k = e.createElement(t); a = e.getElementsByTagName(t)[0];
                k.async = 1; k.src = r; a.parentNode.insertBefore(k, a);
            })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=110963911', 'ym');
            ym(110963911, 'init', {
                ssr: true, webvisor: true, clickmap: true,
                referrer: document.referrer, url: location.href,
                accurateTrackBounce: true, trackLinks: true
            });
        }

        /* Показываем баннер, только если выбора ещё не делали. Разметка есть всегда,
           но скрыта в CSS — так согласившийся не видит мигания при загрузке. */
        if (consentValue() === null) banner.classList.add('is-visible');

        banner.addEventListener('click', function (e) {
            if (e.target.closest('[data-cookie-accept]')) {
                remember('1', 365);
                banner.classList.remove('is-visible');
                startMetrika();
                return;
            }
            if (e.target.closest('[data-cookie-decline]')) {
                remember('0', 180);
                banner.classList.remove('is-visible');
                /* Отказ должен подействовать СРАЗУ, а не со следующей страницы. У того,
                   кто ничего не выбирал, счётчик на этой странице уже работает (базовый
                   уровень), и выгрузить Метрику из загруженной страницы нельзя: она
                   продолжила бы дотягивать время на странице и переходы по ссылкам.
                   Поэтому перезагружаем — кука '0' уже стоит, сервер счётчик не вставит.
                   Перезагружаем только если Метрика действительно поднята: иначе это
                   была бы бессмысленная перерисовка страницы на каждый отказ. */
                if (window.ym) { location.reload(); }
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
