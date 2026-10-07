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
 * Срок хранения выбора — 30 дней, одинаково для согласия и для отказа: так
 * записано в задаче («после нажатия пропадает на 30 дней»). Раньше здесь было
 * 365 дней на согласие и 180 на отказ — вернули к ТЗ 2026-09-08.
 * Следствие, которое надо понимать: раз в месяц баннер увидит и тот, кто уже
 * нажимал «Принять». Это осознанно; трогать срок — только по просьбе заказчика.
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
                <?/* «Продолжая пользоваться сайтом, вы соглашаетесь с обработкой данных» убрано 07.10.2026:
                     это согласие действием, а не отдельное согласие (152-ФЗ); так же на latitudo.ru и easydecking. */?>
                <span class="cookie-banner__desc">Часть из них нужна сайту для работы, остальные помогают нам понять,
                    как им пользуются, и сделать его удобнее. Сайт использует cookie и аналитику согласно
                    <a class="cookie-banner__link js-doc-popup" href="/policy" data-src="#doc-policy">политике конфиденциальности</a>.</span>
                <span class="cookie-banner__short">Сайт использует <a class="cookie-banner__link js-doc-popup" href="/policy" data-src="#doc-policy">cookie-файлы</a></span>
            </p>
            <span class="cookie-banner__actions">
                <button type="button" class="cookie-banner__btn" data-cookie-accept><span class="cookie-banner__btn-wide">Принять</span><span class="cookie-banner__btn-narrow">Хорошо</span></button>
            </span>
        </div>
    </div>

    <script src="<?= SITE_TEMPLATE_PATH ?>/js/cookie-banner.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/js/cookie-banner.js') ?>"></script>
    <?
}
