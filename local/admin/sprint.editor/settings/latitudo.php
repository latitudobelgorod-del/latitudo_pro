<?php
/**
 * Настройки редактора Sprint.Editor для latitudo.pro.
 *
 * Раньше файл лежал только на проде в bitrix/admin/sprint.editor/settings/ — вне git,
 * локально его не было. Модуль ищет настройки сначала в /local/admin/sprint.editor/settings/
 * (Module::getAdminSubDir(), bitrix/modules/sprint.editor/lib/module.php:36-43), поэтому
 * версионируемая копия живёт здесь и уезжает на прод вместе с кодом. Проверка каталога
 * идёт отдельно по каждому подкаталогу, так что snippets и packs по-прежнему берутся
 * из bitrix/admin/sprint.editor/.
 *
 * Колонки: col-md-* — доли 12-колоночной сетки, col-md-40/col-md-60 — наши 40/60%,
 * которых в 12 колонках не выразить. Их ширины заданы в styles.css шаблона
 * (.section--editor .col-md-40 / .col-md-60).
 */

$settings = array(
    'title' => 'latitudo.pro',

    'layout_classes' => array(
        'type1' => array(
            array('col-md-3', 'col-md-4', 'col-md-6', 'col-md-8', 'col-md-9', 'col-md-12', 'col-md-40', 'col-md-60')
        ),
        'type2' => array(
            array('col-md-3', 'col-md-4', 'col-md-6', 'col-md-8', 'col-md-9', 'col-md-12', 'col-md-40', 'col-md-60'),
        ),
        'type3' => array(
            array('col-md-3', 'col-md-4', 'col-md-6', 'col-md-8', 'col-md-9', 'col-md-12', 'col-md-40', 'col-md-60'),
        ),
        'type4' => array(
            array('col-md-3', 'col-md-4', 'col-md-6', 'col-md-8', 'col-md-9', 'col-md-12', 'col-md-40', 'col-md-60'),
        ),
    ),

    'layout_enabled'   => [
        'layout_1',
        'layout_2',
        'layout_3',
        'layout_4',
    ],

    'layout_defaults' => array(
        'type1' => '',
        'type2' => 'col-md-6',
        'type3' => 'col-md-4',
        'type4' => 'col-md-3',
    ),

    'layout_titles' => array(
        'col-md-12' => '100%',
        'col-md-9'  => '75%',
        'col-md-8'  => '66.66%',
        'col-md-60' => '60%',
        'col-md-6'  => '50%',
        'col-md-40' => '40%',
        'col-md-4'  => '33.33%',
        'col-md-3'  => '25%',
    ),

    'block_settings' => array(
        'text' => array(

            'csslist' => array(
                'type' => 'hidden',
                'value' => array(
                    'sp-text-1' => 'Стиль 1',
                    'sp-text-2' => 'Стиль 2',
                    'gr_block'  => 'Зеленая заливка блока',
                )

            )
        ),
        'iblock_elements' => array(

            'param1' => array(
                'type'  => 'select',
                'value' => array(
                    'style1' => 'Шаблон (для страницы услуг)',
                    'style2' => 'Шаблон (как в Портфолио)',
                ),
            ),
        ),

    ),

    'snippets' => [
        [
            'file'        => 'brands_catalog.php',
            'title'       => 'brands_catalog',
            'description' => '<strong>Пример сниппета</strong>',
        ],
        [
            'file'        => 'kupit_dosky.php',
            'title'       => 'kupit_dosky',
            'description' => '<strong>Пример сниппета</strong>',
        ],
    ],

);

// Список веб-форм для блоков с формой. Модуль form из нашей редакции убран
// (LICENSE_VIOLATION), поэтому IncludeModule вернёт false и настройки просто не добавятся —
// код оставлен как есть, чтобы файл работал и там, где модуль присутствует.
$formlist = [];
if (\CModule::IncludeModule('form')) {
    $by = 's_name';
    $order = 'asc';
    $isfiltered = null;
    $dbres = \CForm::GetList($by, $order, array(
        'ACTIVE' => 'Y'
    ), $isfiltered);

    while ($form = $dbres->Fetch()) {
        $formlist[$form['SID']] = $form['NAME'];
    }
}

if (!empty($formlist)) {
    $settings['block_settings']['button_link'] = array(
        'form_id' => array(
            'type' => 'select',
            'value' => $formlist
        ),
    );
}

if (!empty($formlist)) {
    $settings['block_settings']['form_inline'] = array(
        'form_id' => array(
            'type' => 'select',
            'value' => $formlist
        ),
    );
}
