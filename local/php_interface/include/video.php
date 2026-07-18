<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Блок «Посмотрите наши видео» — слайдер роликов YouTube на лендинге раздела каталога.
 *
 * Контент НЕ в коде, а в админке у раздела «Каталога продукции» (ID=3), в двух
 * пользовательских полях (заводятся скриптом tools/setup-video-slider.php):
 *   UF_SHOW_VIDEO   — галочка «Показывать слайдер с видео на странице»;
 *   UF_VIDEO_SLIDER — множественное строковое «Видео для слайдера в разделе» (ссылки YouTube).
 *
 * Галочка снята или список пуст → функция не выводит НИЧЕГО (как блок акций).
 * Благодаря этому одна строка latitudoShowVideosForSection('zabory') в лендинге
 * работает на любом разделе: контент-менеджер включает блок сам, без разработчика.
 *
 * Обложки слайдов берём со штатных превью YouTube (i.ytimg.com), сами ролики
 * открываются в Fancybox по клику — до клика не грузится ни один iframe.
 *
 * Макет: Figma 537:20945 (раунд 4, лендинг «Заборы»).
 */

use Bitrix\Main\Loader;

/**
 * ID ролика из ссылки YouTube в любом ходовом формате:
 *   youtube.com/watch?v=ID · youtu.be/ID · youtube.com/embed/ID · youtube.com/shorts/ID
 * Кривая строка (опечатка, ссылка на другой сервис) → null: такой ролик молча
 * пропускаем, чтобы одна ошибка в админке не роняла весь блок.
 */
function latitudoYoutubeId(string $url): ?string
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    // ID у YouTube — ровно 11 символов латиницы/цифр/дефиса/подчёркивания.
    // Паттерн заякорен на схему и домен (^https?://…): иначе ссылка вида
    // «https://чужой-сайт/redirect?to=youtube.com/embed/XXXXXXXXXXX» тоже считалась бы
    // валидной, и в блоке молча появился бы не тот ролик, что вставили в админке.
    // Хвостовой (?![A-Za-z0-9_-]) не даёт откусить первые 11 символов от более длинного
    // токена — такая ссылка честно отбраковывается, а не показывает случайное видео.
    // Схема необязательна: контент-менеджер может вставить «youtube.com/watch?v=…» без https://.
    $pattern = '~^(?:https?://)?(?:www\.|m\.)?(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})(?![A-Za-z0-9_-])~i';

    return preg_match($pattern, $url, $m) ? $m[1] : null;
}

/**
 * Список роликов для лендинга раздела: [['id','poster','poster_fallback','embed'], …].
 * Пустой массив = блок показывать не надо.
 */
function latitudoSectionVideos(string $sectionSlug, int $catalogIblockId = LATITUDO_CATALOG_IBLOCK_ID): array
{
    // Кэш в рамках запроса — как в latitudoCatalogSectionId(): чтобы повторный вызов
    // не ходил в БД второй раз за ту же страницу.
    static $cache = [];
    $key = $catalogIblockId . '|' . $sectionSlug;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    if (!Loader::includeModule('iblock')) {
        return $cache[$key] = [];
    }
    // Раздел ищем по стабильному якорю XML_ID, а не по символьному коду: код
    // перегенерируется при переименовании раздела в админке (см. catalog-sections.php).
    $sectionId = latitudoCatalogSectionId($sectionSlug, $catalogIblockId);
    if (!$sectionId) {
        return $cache[$key] = [];
    }

    $section = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $catalogIblockId, 'ID' => $sectionId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID', 'UF_SHOW_VIDEO', 'UF_VIDEO_SLIDER']
    )->GetNext(false, false);

    if (!$section) {
        return $cache[$key] = [];
    }
    // Галочка выключена (или поле ещё не заведено в этой базе) — блока нет.
    if ((string)($section['UF_SHOW_VIDEO'] ?? '') !== '1') {
        return $cache[$key] = [];
    }

    $raw    = $section['UF_VIDEO_SLIDER'] ?? [];
    $videos = [];
    foreach ((is_array($raw) ? $raw : [$raw]) as $url) {
        $id = latitudoYoutubeId((string)$url);
        if ($id === null || isset($videos[$id])) {
            continue; // мусор и дубли пропускаем
        }
        // Все URL ниже собираются из $id, а он ограничен regex-группой [A-Za-z0-9_-]{11}.
        // ВАЖНО: не подставлять сюда сырой $url из админки — на этом держится защита от XSS.
        $videos[$id] = [
            'id' => $id,
            // maxresdefault есть не у всех роликов → подстраховка в swapPoster() ниже
            'poster'          => "https://i.ytimg.com/vi/{$id}/maxresdefault.jpg",
            'poster_fallback' => "https://i.ytimg.com/vi/{$id}/hqdefault.jpg",
            // nocookie-домен: YouTube не ставит рекламные куки до старта просмотра
            'embed'           => "https://www.youtube-nocookie.com/embed/{$id}?autoplay=1&rel=0",
        ];
    }

    return $cache[$key] = array_values($videos);
}

/** Блок видео на лендинге раздела — одна строка на странице. */
function latitudoShowVideosForSection(string $sectionSlug): void
{
    $videos = latitudoSectionVideos($sectionSlug);
    if ($videos) {
        latitudoShowVideos($videos);
    }
}

/**
 * Рисует секцию «Посмотрите наши видео».
 *
 * Вёрстка по макету: слайд 1320×600 (r=20) с затемнением 20% и белой иконкой play 80×80,
 * между слайдами 24px, соседний слайд «выглядывает» за край экрана — поэтому карусель
 * вынесена ЗА .container и центрируется через centeredSlides.
 */
function latitudoShowVideos(array $videos): void
{
    if (!$videos) {
        return;
    }
    $hasSlider = count($videos) > 1; // один ролик — стрелки и точки не нужны

    // Уникальная метка экземпляра. Скрипт ниже находит «свой» блок по ней, а не по
    // id="video": если блок когда-нибудь выведут дважды на одной странице,
    // getElementById вернул бы обеим копиям ПЕРВУЮ, и второй слайдер остался бы мёртвым.
    static $instance = 0;
    $uid = 'video-slider-' . (++$instance);
    ?>
    <section class="section video" id="video">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Посмотрите наши видео</h2>
            </div>
        </div>

        <div class="video-slider" data-video-slider="<?= $uid ?>">
            <div class="video-slider__viewport swiper">
                <div class="swiper-wrapper">
                    <? foreach ($videos as $video): ?>
                    <div class="swiper-slide video-slider__slide">
                        <a class="video-card"
                           href="<?= htmlspecialcharsbx($video['embed']) ?>"
                           data-fancybox="<?= $uid ?>"
                           aria-label="Смотреть видео">
                            <? // Запасная обложка — в data-атрибуте, подмена в скрипте ниже
                               // (onerror тут недостаточно, см. комментарий у swapPoster). ?>
                            <img class="video-card__img"
                                 src="<?= htmlspecialcharsbx($video['poster']) ?>"
                                 data-poster-fallback="<?= htmlspecialcharsbx($video['poster_fallback']) ?>"
                                 <? // alt пустой намеренно: картинка декоративная, смысл несёт
                                    // aria-label ссылки. Названий роликов в полях раздела нет. ?>
                                 alt="" loading="lazy">
                            <span class="video-card__play" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="80" height="80" fill="none">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M10 8.2v7.6l6.2-3.8z" fill="currentColor"/>
                                </svg>
                            </span>
                        </a>
                    </div>
                    <? endforeach ?>
                </div>
            </div>

            <? if ($hasSlider): ?>
            <button type="button" class="video-slider__nav video-slider__nav--prev" aria-label="Предыдущее видео">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 12H5"/><path d="m11 6-6 6 6 6"/>
                </svg>
            </button>
            <button type="button" class="video-slider__nav video-slider__nav--next" aria-label="Следующее видео">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 12h15"/><path d="m13 6 6 6-6 6"/>
                </svg>
            </button>
            <div class="video-slider__dots"></div>
            <? endif ?>
        </div>
    </section>

    <script>
    (function () {
        var root = document.querySelector('[data-video-slider="<?= $uid ?>"]');
        if (!root) return;

        /**
         * Обложка ролика: maxresdefault (1280px) есть не у всех видео. На отсутствующую
         * YouTube отдаёт не ошибку, а СЕРУЮ ЗАГЛУШКУ 120×90 — браузер считает её нормально
         * загруженной картинкой, поэтому onerror молчит, и заглушка растянулась бы на весь
         * слайд. Надёжный признак — реальный размер: всё, что ≤120px, это заглушка или
         * ошибка → подменяем на hqdefault (480×360), он есть у любого ролика.
         */
        function swapPoster(img) {
            var fb = img.getAttribute('data-poster-fallback');
            if (fb && (!img.naturalWidth || img.naturalWidth <= 120)) {
                img.removeAttribute('data-poster-fallback'); // одна попытка, без циклов
                img.src = fb;
            }
        }

        function bindPosters() {
            root.querySelectorAll('.video-card__img').forEach(function (img) {
                var check = function () { swapPoster(img); };
                if (img.complete) check();          // картинка из кэша — события уже прошли
                img.addEventListener('load', check);
                img.addEventListener('error', check);
            });
        }

        function init() {
            bindPosters();
            var el   = root.querySelector('.video-slider__viewport');
            var prev = root.querySelector('.video-slider__nav--prev');
            var dots = root.querySelector('.video-slider__dots');
            if (window.Swiper && el && !el.swiper) {
                var opts = {
                    slidesPerView: 'auto',
                    spaceBetween: 24,
                    centeredSlides: true    // активный слайд по центру, соседний выглядывает — как в макете
                };
                // При одном ролике стрелок и точек в разметке нет — не передаём Swiper'у
                // конфиг с пустыми el, чтобы не зависеть от того, как их переварит библиотека.
                if (prev) {
                    opts.navigation = { prevEl: prev, nextEl: root.querySelector('.video-slider__nav--next') };
                }
                if (dots) {
                    opts.pagination = { el: dots, clickable: true };
                }
                new Swiper(el, opts);
            }
            // Ролик открывается только по клику — до этого ни один iframe не грузится.
            if (window.Fancybox) {
                Fancybox.bind('[data-fancybox="<?= $uid ?>"]', { Thumbs: false });
            }
        }

        // Swiper и Fancybox подключены с defer — на DOMContentLoaded уже готовы.
        if (window.Swiper && window.Fancybox) init();
        else window.addEventListener('DOMContentLoaded', init);
    })();
    </script>
    <?
}
