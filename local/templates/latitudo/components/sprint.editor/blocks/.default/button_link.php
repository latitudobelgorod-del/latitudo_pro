<?php
/**
 * Блок «Кнопка-ссылка» редактора Sprint.Editor — наша версия.
 *
 * Зачем переопределяем. Штатный шаблон модуля (bitrix/components/sprint.editor/blocks/
 * templates/.default/button_link.php) выводит кнопку ТОЛЬКО когда заполнены и текст,
 * и ссылка, причём ссылка обязана начинаться с http(s):, mailto: или «/». Контент-менеджер
 * ставит кнопку «Оставить заявку…», ссылку не заполняет — и блок молча исчезает со страницы.
 *
 * Что делает наша версия:
 *   • ссылка пустая  → кнопка открывает общую модалку заявки (триггер .js-request-form,
 *     заголовок окна = текст кнопки, см. local/php_interface/include/request-form.php);
 *   • ссылка задана   → обычная ссылка, но принимаем ещё якоря (#contacts) и относительные
 *     пути (stupeni) — штатный шаблон их отбрасывал;
 *   • чужая схема (javascript:, data: и прочее) — ссылку игнорируем и показываем кнопку
 *     заявки: контент-менеджер видит результат, а вредоносный href на страницу не попадает.
 *
 * Класс sp-button_link намеренно НЕ ставим: в _style.css модуля он серый со скруглением 20px
 * и перебивает наш вид. Оформление берём из общих .btn/.btn--primary шаблона.
 *
 * Путь файла ищет findResource() компонента (bitrix/components/sprint.editor/blocks/class.php:365)
 * — шаблон сайта имеет наивысший приоритет, файл модуля при этом остаётся нетронутым.
 *
 * @var array $block
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$title = trim((string)($block['title'] ?? ''));
if ($title === '') {
    return; // без подписи кнопки нет — показывать нечего
}

$url = trim((string)($block['url'] ?? ''));

// Белый список схем. Относительный путь (без «схема:» в начале) тоже пропускаем.
$hasScheme = (bool)preg_match('~^[a-z][a-z0-9+.\-]*:~i', $url);
$isAllowed = (bool)preg_match('~^(?:https?://|mailto:|tel:|/|\#|\?)~i', $url);
if ($url !== '' && $hasScheme && !$isAllowed) {
    $url = '';
}

if ($url === ''):
    // Кнопка-триггер модалки заявки. data-form-title подставляется в заголовок окна.
    ?><button type="button" class="btn btn--primary editor-cta js-request-form"
        data-form-title="<?= htmlspecialcharsbx($title) ?>"><?= htmlspecialcharsbx($title) ?></button><?php
    return;
endif;

$newTab = !empty($block['target']);
?><a class="btn btn--primary editor-cta" href="<?= htmlspecialcharsbx($url) ?>"<?php
    if ($newTab): ?> target="<?= htmlspecialcharsbx((string)$block['target']) ?>" rel="noopener"<?php endif;
?>><?= htmlspecialcharsbx($title) ?></a>
