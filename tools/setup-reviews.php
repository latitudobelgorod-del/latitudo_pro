<?php
/**
 * Миграция блока «Отзывы». Идемпотентна: повторный запуск ничего не ломает.
 *
 * Зачем нужна: база у локалки и у сервера РАЗНЫЕ, поэтому одинаковую структуру
 * инфоблоков надо создать в каждой. Запускать после `git pull`:
 *
 *   локально (Git Bash):  C:/OSPanel/modules/PHP-8.2/php-cgi.exe -f tools/setup-reviews.php
 *   на Beget:             cd ~/latituty.beget.tech/public_html && /usr/local/bin/php8.2 tools/setup-reviews.php
 *
 * Что делает:
 *   1. Создаёт инфоблок «Отзывы» (код REVIEWS) с полями, если его ещё нет.
 *   2. Добавляет свойства RATING (оценка) и PHOTOS (фото к отзыву).
 *   3. Добавляет «Магазинам / Регионам» (ID=6) поля рейтинга Яндекс.Карт — свои у каждого филиала.
 *   4. Добавляет разделам «Каталога продукции» (ID=3) галочку UF_SHOW_REVIEWS.
 *   5. Выдаёт гостям право на чтение инфоблока «Отзывы» (иначе news.list отдаст «Раздел не найден»).
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

const STORES_IBLOCK_ID  = 6;              // Магазины / Регионы
const CATALOG_IBLOCK_ID = 3;              // Каталог продукции
const IBLOCK_TYPE       = 'latitudo_content';
const REVIEWS_CODE      = 'REVIEWS';

// Права как у остальных инфоблоков проекта: 1=админы(X), 2=все пользователи(R), 6=контент-редакторы(W)
const IBLOCK_PERMISSIONS = [1 => 'X', 2 => 'R', 6 => 'W'];

function say(string $msg): void { echo $msg . "\n"; }

// ─── 1. Инфоблок «Отзывы» ────────────────────────────────────────────────────
$existing = CIBlock::GetList([], ['TYPE' => IBLOCK_TYPE, 'CODE' => REVIEWS_CODE, 'CHECK_PERMISSIONS' => 'N'])->Fetch();

if ($existing) {
    $reviewsId = (int)$existing['ID'];
    say("· Инфоблок «Отзывы» уже есть (ID={$reviewsId}).");
} else {
    $ib = new CIBlock();
    $reviewsId = (int)$ib->Add([
        'ACTIVE'         => 'Y',
        'IBLOCK_TYPE_ID' => IBLOCK_TYPE,
        'LID'            => ['s1'],
        'CODE'           => REVIEWS_CODE,
        'NAME'           => 'Отзывы',
        'SORT'           => 500,
        'INDEX_ELEMENT'  => 'N',
        'INDEX_SECTION'  => 'N',
        'FIELDS'         => [
            'NAME'         => ['NAME' => 'Имя автора',   'IS_REQUIRED' => 'Y'],
            'PREVIEW_TEXT' => ['NAME' => 'Текст отзыва', 'IS_REQUIRED' => 'Y'],
            'ACTIVE_FROM'  => ['NAME' => 'Дата отзыва',  'IS_REQUIRED' => 'Y'],
        ],
    ]);

    if (!$reviewsId) {
        exit("Не удалось создать инфоблок «Отзывы»: {$ib->LAST_ERROR}\n");
    }
    say("+ Инфоблок «Отзывы» создан (ID={$reviewsId}).");
}

// ─── 2–3. Свойства ───────────────────────────────────────────────────────────
$props = [
    [
        'IBLOCK_ID'     => $reviewsId,
        'NAME'          => 'Оценка (1–5)',
        'CODE'          => 'RATING',
        'PROPERTY_TYPE' => 'N',
        'MULTIPLE'      => 'N',
        'IS_REQUIRED'   => 'Y',
        'DEFAULT_VALUE' => '5',
        'ACTIVE'        => 'Y',
        'SORT'          => 100,
    ],
    [
        'IBLOCK_ID'     => $reviewsId,
        'NAME'          => 'Фото к отзыву',
        'CODE'          => 'PHOTOS',
        'PROPERTY_TYPE' => 'F',
        'MULTIPLE'      => 'Y',
        'ACTIVE'        => 'Y',
        'SORT'          => 110,
        'FILE_TYPE'     => 'jpg, jpeg, png, webp, gif',
    ],
    [
        'IBLOCK_ID'     => STORES_IBLOCK_ID,
        'NAME'          => 'Рейтинг Яндекс.Карт',
        'CODE'          => 'YANDEX_RATING',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE'      => 'N',
        'ACTIVE'        => 'Y',
        'SORT'          => 600,
        'HINT'          => 'Например: 5,0',
    ],
    [
        'IBLOCK_ID'     => STORES_IBLOCK_ID,
        'NAME'          => 'Количество оценок на Яндексе',
        'CODE'          => 'YANDEX_RATING_COUNT',
        'PROPERTY_TYPE' => 'N',
        'MULTIPLE'      => 'N',
        'ACTIVE'        => 'Y',
        'SORT'          => 610,
        'HINT'          => 'Например: 91',
    ],
    [
        'IBLOCK_ID'     => STORES_IBLOCK_ID,
        'NAME'          => 'Ссылка на отзывы в Яндекс.Картах',
        'CODE'          => 'YANDEX_REVIEWS_URL',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE'      => 'N',
        'ACTIVE'        => 'Y',
        'SORT'          => 620,
        'HINT'          => 'Полный URL карточки компании на Яндекс.Картах',
    ],
];

$obProp = new CIBlockProperty();
foreach ($props as $arFields) {
    $has = CIBlockProperty::GetList([], ['IBLOCK_ID' => $arFields['IBLOCK_ID'], 'CODE' => $arFields['CODE']])->Fetch();
    if ($has) {
        say("· Свойство {$arFields['CODE']} (инфоблок {$arFields['IBLOCK_ID']}) уже есть.");
        continue;
    }
    say($obProp->Add($arFields)
        ? "+ Свойство {$arFields['CODE']} добавлено в инфоблок {$arFields['IBLOCK_ID']}."
        : "! Свойство {$arFields['CODE']}: {$obProp->LAST_ERROR}");
}

// ─── 4. UF_SHOW_REVIEWS — галочка у разделов каталога ────────────────────────
// У разделов инфоблока в Битриксе не «свойства», а пользовательские поля (UF_*).
$ufEntity = 'IBLOCK_' . CATALOG_IBLOCK_ID . '_SECTION';
$ufType   = new CUserTypeEntity();
$hasUF    = $ufType->GetList([], ['ENTITY_ID' => $ufEntity, 'FIELD_NAME' => 'UF_SHOW_REVIEWS'])->Fetch();

if ($hasUF) {
    say('· Поле разделов UF_SHOW_REVIEWS уже есть.');
} else {
    $ufId = $ufType->Add([
        'ENTITY_ID'         => $ufEntity,
        'FIELD_NAME'        => 'UF_SHOW_REVIEWS',
        'USER_TYPE_ID'      => 'boolean',
        'MULTIPLE'          => 'N',
        'MANDATORY'         => 'N',
        'SORT'              => 700,
        'SETTINGS'          => ['DEFAULT_VALUE' => 1, 'DISPLAY' => 'CHECKBOX'],
        'EDIT_FORM_LABEL'   => ['ru' => 'Показывать отзывы на странице раздела'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Отзывы'],
        'LIST_FILTER_LABEL' => ['ru' => 'Отзывы'],
        'HELP_MESSAGE'      => ['ru' => 'Не трогать = отзывы показываются (по умолчанию «да»).'],
    ]);
    say($ufId ? "+ Поле разделов UF_SHOW_REVIEWS добавлено (ID={$ufId})." : '! Не удалось добавить UF_SHOW_REVIEWS.');
}

// ─── 5. Права на чтение для гостей ───────────────────────────────────────────
// Без права R у группы 2 штатные компоненты на публичной части отдают «Раздел не найден».
if (!CIBlock::GetGroupPermissions($reviewsId)) {
    CIBlock::SetPermission($reviewsId, IBLOCK_PERMISSIONS);
    say("+ Права инфоблока «Отзывы» выставлены: 1=X, 2=R, 6=W.");
} else {
    say('· Права инфоблока «Отзывы» уже заданы — не трогаем.');
}

say("\nГотово. ID инфоблока «Отзывы»: {$reviewsId}");
