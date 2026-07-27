<?php
/**
 * Блок «Таблица» редактора Sprint.Editor — наша версия.
 *
 * Зачем переопределяем. Штатный шаблон печатает текст ячейки, пропущенный через
 * Sprint\Editor\Blocks\Table::prepareColumn() (bitrix/modules/sprint.editor/lib/blocks/table.php:22),
 * а тот делает htmlspecialcharsbx(). Контент-менеджер набирает ячейки в визуальном
 * редакторе, то есть кладёт туда HTML — в итоге на странице видны сами теги
 * «<p><strong>Каркас</strong></p>», а жирность и абзацы не работают.
 *
 * Что делаем: атрибуты ячейки (class/style/colspan/rowspan) по-прежнему берём у
 * prepareColumn — они там корректно экранированы, а текст пропускаем через CBXSanitizer
 * на среднем уровне. Это белый список: p, strong, b, i, br, ul/ol/li, a[href], img и
 * прочая разметка форматирования проходят, а скрипты, обработчики событий и
 * javascript:-ссылки вырезаются. Просто снять экранирование было бы дырой: содержимое
 * приходит из поля инфоблока, а не из кода.
 *
 * Путь ищет findResource() компонента (bitrix/components/sprint.editor/blocks/class.php:365):
 * шаблон сайта приоритетнее, файл модуля остаётся нетронутым и переживёт обновление.
 *
 * @var array $block
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$rows = (array)($block['rows'] ?? []);
if (!$rows) {
    return;
}

$sanitizer = new CBXSanitizer();
$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
$sanitizer->ApplyDoubleEncode(false);
?>
<div class="sp-block-table editor-table">
    <table>
        <?php foreach ($rows as $cols): ?>
            <tr>
                <?php foreach ((array)$cols as $col):
                    $html = trim((string)($col['text'] ?? ''));
                    $html = $html !== '' ? $sanitizer->SanitizeHtml($html) : '';
                    $col  = Sprint\Editor\Blocks\Table::prepareColumn($col);
                    ?>
                    <td<?php
                        if ($col['class']):   ?> class="<?= $col['class'] ?>"<?php endif;
                        if ($col['style']):   ?> style="<?= $col['style'] ?>"<?php endif;
                        if ($col['colspan']): ?> colspan="<?= $col['colspan'] ?>"<?php endif;
                        if ($col['rowspan']): ?> rowspan="<?= $col['rowspan'] ?>"<?php endif;
                    ?>><?= $html ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
