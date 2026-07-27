<?php
/**
 * Блок «Видео» редактора Sprint.Editor — наша версия.
 *
 * Задача: показать нашу кнопку play из макета вместо родной кнопки rutube.
 * Дотянуться стилями внутрь чужого iframe нельзя, поэтому работаем снаружи:
 *
 *   • у блока задано превью → до клика показываем картинку со своей кнопкой,
 *     а плеер вставляем только по клику. Логотипов и панелей rutube до старта
 *     не видно вообще — это и есть вид из макета;
 *   • превью не задано → iframe рисуется сразу, наша кнопка лежит поверх него
 *     по центру, накрывая родную. По клику кнопка убирается.
 *
 * В обоих случаях клик перезапускает плеер с autoplay, поэтому видео стартует
 * с первого нажатия, а не после второго уже по кнопке rutube. Для этого iframe
 * получает allow="autoplay" — без него браузер программный старт запретит.
 *
 * @var array $block
 * @var SprintEditorBlocksComponent $this
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!function_exists('latitudoRutubeThumb')) {
    /**
     * Кадр-обложка ролика rutube по его id — через открытый API сервиса.
     * Результат (в том числе неудачный, пустой) кладём в кэш на 30 дней: чужой сервис
     * нельзя дёргать на каждый показ страницы, а таймаут 3 секунды не даёт странице
     * зависнуть, если rutube недоступен.
     */
    function latitudoRutubeThumb(string $videoId): string
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(2592000, 'thumb-' . $videoId, '/latitudo/rutube')) {
            return (string)$cache->getVars();
        }

        $url = '';
        try {
            $http = new \Bitrix\Main\Web\HttpClient(['socketTimeout' => 3, 'streamTimeout' => 3]);
            $json = $http->get('https://rutube.ru/api/video/' . $videoId . '/');
            $data = $json ? json_decode($json, true) : null;
            $candidate = (string)($data['thumbnail_url'] ?? '');
            // Пускаем только https-картинку с домена rutube/их CDN.
            if (preg_match('~^https://[a-z0-9.\-]+\.(?:ru|com)/~i', $candidate)) {
                $url = $candidate;
            }
        } catch (\Throwable $e) {
            $url = '';
        }

        if ($cache->startDataCache()) {
            $cache->endDataCache($url);
        }
        return $url;
    }
}

$videoHtml = (string)\Sprint\Editor\Blocks\Video::getHtml($block);
if (trim($videoHtml) === '') {
    return;
}

// Разрешаем автозапуск (и фуллскрин заодно) — только если атрибута ещё нет.
if (!preg_match('~<iframe[^>]*\ballow=~i', $videoHtml)) {
    $videoHtml = preg_replace('~<iframe\b~i', '<iframe allow="autoplay; fullscreen"', $videoHtml, 1);
}

// Превью — вложенный подблок-картинка: ['file' => [...], 'desc' => '', 'name' => 'image'].
// Проверяем именно file: сам массив непустой и без загруженной картинки.
$preview = [];
if (!empty($block['preview']['file'])) {
    $preview = \Sprint\Editor\Blocks\Image::getImage(
        $block['preview'],
        ['width' => 1280, 'height' => 720, 'exact' => 0]
    );
}
$posterSrc = (string)($preview['SRC'] ?? '');

// Превью вручную не задано — берём кадр-обложку самого видео. У rutube есть открытый
// API, поэтому заставка получается «родная» и контент-менеджеру грузить ничего не надо.
if ($posterSrc === '' && preg_match(
    '~rutube\.ru/(?:video|play/embed)/([0-9a-f]{32})|pl_video=([0-9a-f]{32})~i',
    (string)($block['url'] ?? ''),
    $m
)) {
    $posterSrc = latitudoRutubeThumb($m[1] ?: $m[2]);
}
?>
<div class="sp-video editor-video" data-editor-video>
    <?php if ($posterSrc !== ''): ?>
        <img class="editor-video__poster" src="<?= htmlspecialcharsbx($posterSrc) ?>"
             alt="<?= htmlspecialcharsbx((string)($preview['DESCRIPTION'] ?? '')) ?>">
        <?php // Плеер лежит в <template> и в DOM не попадает, пока не нажали play. ?>
        <template data-editor-video-frame><?= $videoHtml ?></template>
    <?php else: ?>
        <?= $videoHtml ?>
    <?php endif; ?>
    <button type="button" class="editor-video__play" aria-label="Смотреть видео">
        <img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/video-play.svg" width="44" height="44" alt="" aria-hidden="true">
    </button>
</div>
<?php
// Обработчик один на страницу, сколько бы блоков с видео на ней ни было.
static $scriptPrinted = false;
if ($scriptPrinted) {
    return;
}
$scriptPrinted = true;
?>
<script>
(function () {
    function withAutoplay(src) {
        if (!src || src.indexOf('autoplay=') > -1) return src;
        return src + (src.indexOf('?') > -1 ? '&' : '?') + 'autoplay=1';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.editor-video__play');
        if (!btn) return;

        var box = btn.closest('[data-editor-video]');
        if (!box) return;

        var tpl = box.querySelector('template[data-editor-video-frame]');
        if (tpl) {
            /* Превью-режим: вставляем плеер, картинку убираем. */
            box.insertAdjacentHTML('beforeend', tpl.innerHTML);
            tpl.remove();
            var poster = box.querySelector('.editor-video__poster');
            if (poster) poster.remove();
        }

        var frame = box.querySelector('iframe');
        if (frame) frame.src = withAutoplay(frame.getAttribute('src'));

        btn.remove();
    });
})();
</script>
