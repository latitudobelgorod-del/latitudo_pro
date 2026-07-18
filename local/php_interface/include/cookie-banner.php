<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Баннер согласия на использование cookie-файлов.
 * Figma (раунд 4): десктоп 537:19338 — полоса внизу экрана; смартфон 537:39883 —
 * карточка внизу поверх затемнения.
 *
 * Как работает: разметка есть всегда, но в CSS баннер скрыт (.cookie-banner{display:none}).
 * Показывает его JS — и только если согласия ещё нет. Так тот, кто уже нажал «Принять»,
 * не увидит мигания баннера при загрузке страницы.
 *
 * Хранение выбора: cookie latitudo_cookie_consent=1 на год. На проде домен ставим
 * с точкой (.latitudo.pro) — тогда согласие, данное на msk.latitudo.pro, действует
 * и на krd/vrn/belgorod/rnd, и баннер не спрашивают на каждом поддомене заново.
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
                <span class="cookie-banner__desc">Они помогают сайту работать корректно и улучшают ваш пользовательский опыт.</span>
            </p>
            <button type="button" class="cookie-banner__btn" data-cookie-accept>Принять</button>
        </div>
    </div>

    <script>
    (function () {
        var NAME = 'latitudo_cookie_consent';
        var banner = document.getElementById('cookie-banner');
        if (!banner) return;

        function hasConsent() {
            return document.cookie.split('; ').indexOf(NAME + '=1') !== -1;
        }

        /* Домен для cookie: на поддоменах прода — общий .latitudo.pro (одно согласие
           на все 5 городов). На локалке/ином домене атрибут domain не ставим вообще. */
        function domainAttr() {
            var host = location.hostname;
            var m = host.match(/([^.]+\.[^.]+)$/);
            if (!m || host.indexOf('.') === -1 || /^\d+(\.\d+){3}$/.test(host)) return '';
            return host === 'localhost' ? '' : '; domain=.' + m[1];
        }

        if (!hasConsent()) banner.classList.add('is-visible');

        banner.addEventListener('click', function (e) {
            if (!e.target.closest('[data-cookie-accept]')) return;
            document.cookie = NAME + '=1; path=/; max-age=' + (365 * 24 * 60 * 60)
                + '; SameSite=Lax' + domainAttr();
            banner.classList.remove('is-visible');
        });
    })();
    </script>
    <?
}
