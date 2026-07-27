<?php
/**
 * Блок «Файлы» редактора Sprint.Editor — наша версия.
 *
 * Штатный шаблон модуля выводит голый нумерованный список ссылок, без иконки и
 * оформления. По макету у каждого файла слева стоит иконка документа (скруглённый
 * квадрат Crimson #8B0000), а подписью служит описание, которое контент-менеджер
 * задаёт рядом с файлом.
 *
 * Иконку берём из шаблона (images/icons/file.svg), а не из корневой /images/file_icons.svg:
 * файл в шаблоне лежит под git и уезжает на прод вместе с кодом, отдельно загружать нечего.
 *
 * Подпись: описание файла, если заполнено, иначе имя файла как он был загружен.
 * Атрибут download заставляет браузер скачивать, а не открывать PDF во вкладке.
 *
 * @var array $block
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$files = (array)($block['files'] ?? []);
if (!$files) {
    return;
}
?>
<ul class="editor-files">
    <?php foreach ($files as $item):
        $src = (string)($item['file']['SRC'] ?? '');
        if ($src === '') {
            continue;
        }
        $origName = (string)($item['file']['ORIGINAL_NAME'] ?? '');
        $desc     = trim((string)($item['desc'] ?? ''));
        $label    = $desc !== '' ? $desc : $origName;
        ?>
        <li class="editor-files__item">
            <a class="editor-file" href="<?= htmlspecialcharsbx($src) ?>"
               download="<?= htmlspecialcharsbx($origName) ?>">
                <img class="editor-file__icon" src="<?= SITE_TEMPLATE_PATH ?>/images/icons/file.svg"
                     width="32" height="32" alt="" aria-hidden="true">
                <span class="editor-file__name"><?= htmlspecialcharsbx($label) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
