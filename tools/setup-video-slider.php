<?php
/**
 * Миграция блока «Посмотрите наши видео». Идемпотентна: повторный запуск ничего не ломает.
 *
 * Зачем нужна: база у локалки и у сервера РАЗНЫЕ, поэтому одинаковые пользовательские
 * поля надо завести в каждой. Запускать после `git pull`:
 *
 *   локально (Git Bash):  C:/OSPanel/modules/PHP-8.2/php.exe -d short_open_tag=On -f tools/setup-video-slider.php
 *   на проде (Reg.ru):    ssh regru-latitudo "cd www/latitudo.pro && php tools/setup-video-slider.php"
 *
 * Что делает — добавляет разделам «Каталога продукции» (ID=3) два пользовательских поля:
 *   1. UF_VIDEO_SLIDER — строковое МНОЖЕСТВЕННОЕ: ссылки на ролики YouTube (по одной в строку).
 *   2. UF_SHOW_VIDEO   — да/нет (флажок): показывать блок с видео на странице раздела.
 *
 * По умолчанию флажок ВЫКЛЮЧЕН (в отличие от UF_SHOW_REVIEWS): видео есть не у всех
 * разделов, и молча показывать пустой блок на шести лендингах нам не нужно.
 *
 * ⚠ ГРАБЛИ: поле создано, а в админке его НЕ ВИДНО (вкладка «Доп. поля» пустая или
 * показывает только старые поля). Причина не в кэше и не в правах: если пользователь
 * когда-то сохранял настройки формы раздела, Битрикс кладёт в b_user_option запись
 * `form_section_<IBLOCK_ID>` с ЖЁСТКИМ перечнем полей и рисует форму строго по ней —
 * поля, добавленные позже, в этот список не попадают и просто не выводятся.
 * Лечится сбросом раскладки формы (у каждого пользователя своя!):
 *   DELETE FROM b_user_option WHERE CATEGORY='form' AND NAME='form_section_3';
 *   rm -rf bitrix/managed_cache/MYSQL/user_option
 * Либо руками: шестерёнка справа от вкладок → вернуть настройки формы по умолчанию.
 */
// В CLI DOCUMENT_ROOT либо пустой, либо отсутствует — берём корень проекта от самого файла.
$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $docRoot;

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
require($docRoot . '/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    exit("Модуль iblock не загружен\n");
}

const CATALOG_IBLOCK_ID = 3; // Каталог продукции

function say(string $msg): void { echo $msg . "\n"; }

// У разделов инфоблока в Битриксе не «свойства», а пользовательские поля (UF_*).
$ufEntity = 'IBLOCK_' . CATALOG_IBLOCK_ID . '_SECTION';
$ufType   = new CUserTypeEntity();

$fields = [
    // Список ссылок. MULTIPLE = Y → админка рисует поле с кнопкой «Добавить».
    [
        'ENTITY_ID'         => $ufEntity,
        'FIELD_NAME'        => 'UF_VIDEO_SLIDER',
        'USER_TYPE_ID'      => 'string',
        'MULTIPLE'          => 'Y',
        'MANDATORY'         => 'N',
        'SORT'              => 810,
        'SETTINGS'          => ['SIZE' => 60, 'ROWS' => 1, 'MIN_LENGTH' => 0, 'MAX_LENGTH' => 0, 'DEFAULT_VALUE' => '', 'REGEXP' => ''],
        'EDIT_FORM_LABEL'   => ['ru' => 'Видео для слайдера в разделе'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Видео'],
        'LIST_FILTER_LABEL' => ['ru' => 'Видео'],
        'HELP_MESSAGE'      => ['ru' => 'Ссылки на ролики YouTube — по одной в строке, кнопка «Добавить» под полем. Подходит любой формат ссылки: youtube.com/watch?v=…, youtu.be/…, /shorts/…. Обложка ролика подставляется автоматически. Порядок роликов в слайдере = порядок строк.'],
    ],
    // Выключатель блока.
    [
        'ENTITY_ID'         => $ufEntity,
        'FIELD_NAME'        => 'UF_SHOW_VIDEO',
        'USER_TYPE_ID'      => 'boolean',
        'MULTIPLE'          => 'N',
        'MANDATORY'         => 'N',
        'SORT'              => 800,
        'SETTINGS'          => ['DEFAULT_VALUE' => 0, 'DISPLAY' => 'CHECKBOX'],
        'EDIT_FORM_LABEL'   => ['ru' => 'Показывать слайдер с видео на странице'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Слайдер видео'],
        'LIST_FILTER_LABEL' => ['ru' => 'Слайдер видео'],
        'HELP_MESSAGE'      => ['ru' => 'Галочка снята = блока с видео на странице нет. Галочка стоит, но список ссылок пуст = блок тоже не выводится.'],
    ],
];

foreach ($fields as $arFields) {
    $name = $arFields['FIELD_NAME'];
    $has  = $ufType->GetList([], ['ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $name])->Fetch();
    if ($has) {
        say("· Поле разделов {$name} уже есть (ID={$has['ID']}).");
        continue;
    }
    $id = $ufType->Add($arFields);
    say($id ? "+ Поле разделов {$name} добавлено (ID={$id})." : "! Не удалось добавить {$name}.");
}

say("\nГотово. Поля появятся в админке: Контент → Каталог продукции → Разделы → «Изменить раздел».");
