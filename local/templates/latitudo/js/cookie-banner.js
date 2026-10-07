/**
 * Показ баннера о cookie: разметка — в local/php_interface/include/cookie-banner.php,
 * там же описано, что баннер — уведомление. Скрипт вынесен из страницы в файл
 * 07.10.2026, как на vrn.easydecking.ru и latitudo.ru: проверка 152-ФЗ (vlip.site)
 * ставила «загрузку трекеров до согласия», пока код баннера стоял в странице
 * вперемешку с упоминаниями счётчиков. Подключается сразу после разметки, без defer.
 */
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

    /* Высота баннера → в CSS-переменную. По ней в styles.css приподнимается
       кнопка заказа звонка: она фиксирована в правом нижнем углу и иначе
       наезжает на «Отклонить». Высота зависит от длины текста и ширины экрана,
       поэтому меряем, а не подставляем число. Пересчёт при скрытии и повороте. */
    function syncBannerHeight() {
        var h = banner.classList.contains('is-visible') ? banner.offsetHeight : 0;
        document.documentElement.style.setProperty('--cookie-banner-h', h + 'px');

        /* Высота нижней панели навигации. Баннер должен вставать НАД ней,
           а не поверх: иначе закрывает «Меню / Написать / Позвонить».
           ⚠️ Видимость НЕ проверяем через offsetParent: у position:fixed он
           всегда null, и панель считалась бы скрытой даже на смартфоне.
           offsetHeight сам возвращает 0 при display:none, чего и достаточно:
           на десктопе .tabbar скрыт → переменная 0 → баннер внизу, как был. */
        var tabbar = document.querySelector('.tabbar');
        document.documentElement.style.setProperty(
            '--tabbar-h', (tabbar ? tabbar.offsetHeight : 0) + 'px'
        );
    }
    /* ⚠️ Замер откладываем до готовности DOM. Этот скрипт выводится в
       footer.php РАНЬШЕ, чем <nav class="tabbar"> (строки 92 и 257), поэтому
       при первом проходе querySelector('.tabbar') вернул бы null, переменная
       встала бы в 0 и баннер снова лёг бы поверх панели. */
    syncBannerHeight();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncBannerHeight);
    }
    window.addEventListener('load', syncBannerHeight);
    window.addEventListener('resize', syncBannerHeight);

    banner.addEventListener('click', function (e) {
        /* Обе кнопки делают теперь одно и то же: запоминают нажатие и убирают
           баннер. Больше кнопка ничего не делает (см. шапку cookie-banner.php),
           перезагрузки страницы здесь нет.
           Разные ЗНАЧЕНИЯ куки (1/0) оставлены сознательно: если гейт вернут,
           выбор посетителей уже будет записан и переспрашивать их заново
           не придётся. А вот срок теперь один на оба ответа — 30 дней. */
        if (e.target.closest('[data-cookie-accept]')) {
            remember('1', 30);
            banner.classList.remove('is-visible');
            syncBannerHeight();
            return;
        }
        /* Кнопки «Отклонить» в разметке сейчас НЕТ — убрана по просьбе владельца
           2026-09-14 (сначала на смартфоне, затем везде). Обработчик оставлен
           намеренно: он ничего не стоит, а если кнопку вернут — вместе с гейтом
           или без, — писать куку '0' снова будет нечем. */
        if (e.target.closest('[data-cookie-decline]')) {
            remember('0', 30);
            banner.classList.remove('is-visible');
            syncBannerHeight();
        }
    });

    /* Передумать можно ссылкой «Настройки cookie» в подвале: сбрасываем выбор
       и показываем баннер снова. Отзыв согласия обязан быть не сложнее его выдачи. */
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.js-cookie-settings')) return;
        e.preventDefault();
        remember('', -1);
        banner.classList.add('is-visible');
        syncBannerHeight();
        banner.scrollIntoView({ block: 'nearest' });
    });
})();
