<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Блок «Марквиз» — встроенный квиз-опросник сервиса marquiz.ru на лендинге раздела.
 * На странице достаточно одной строки: <? latitudoShowMarquizForSection('zabory'); ?>
 *
 * Источник — инфоблок с кодом marquiz («Марквизы»). ID инфоблока НЕ хардкодим:
 * на локальной базе и на сервере он может отличаться.
 *
 * Свойства элемента (заводятся скриптом tools/setup-marquiz.php):
 *   MARQUIZ_ID      — идентификатор квиза из кабинета marquiz.ru (24 hex-символа);
 *   MARQUIZ_REGION  — привязка к элементу «Магазины / Регионы» (ID=6);
 *   MARQUIZ_ELEMENT — привязка к разделу «Каталога продукции» (ID=3);
 *   MARQUIZ_TITLE   — заголовок над квизом (необязательный).
 *
 * Какой квиз попадает на страницу (все условия одновременно):
 *   — элемент активен («Активность» = Да; выключил в админке → блока нет);
 *   — регион совпадает с текущим поддоменом (см. region.php);
 *   — раздел совпадает с лендингом; на страницах без раздела берутся элементы,
 *     у которых привязка к разделу пуста.
 * Подходящего квиза нет — не выводится НИЧЕГО (как блок акций), страница цела.
 *
 * ТРЕТЬЯ СТОРОНА. Скрипт-загрузчик marquiz.ru подключается ЛЕНИВО — только когда квиз
 * реально выводится (см. latitudoMarquizLoader). На страницах без квиза чужой JS
 * не грузится вообще: это и быстрее, и меньше лишних куки у посетителя.
 */

use Bitrix\Main\Loader;

const LATITUDO_MARQUIZ_IBLOCK_CODE = 'marquiz';

/** ID инфоблока «Марквизы» по его коду, либо 0. Кэш в рамках запроса. */
function latitudoMarquizIblockId(): int
{
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    if (!Loader::includeModule('iblock')) {
        return $id = 0;
    }
    $res = CIBlock::GetList([], [
        'CODE'              => LATITUDO_MARQUIZ_IBLOCK_CODE,
        'ACTIVE'            => 'Y',
        'CHECK_PERMISSIONS' => 'N',
    ])->Fetch();

    return $id = $res ? (int)$res['ID'] : 0;
}

/**
 * Данные квиза для лендинга: ['id' => …, 'title' => …] либо null.
 * $sectionSlug — slug лендинга (= имя папки); null — страница без раздела (главная).
 */
function latitudoSectionMarquiz(?string $sectionSlug): array|null
{
    static $cache = [];
    $key = (string)$sectionSlug;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $iblockId = latitudoMarquizIblockId();
    if (!$iblockId) {
        return $cache[$key] = null;
    }

    $store = latitudoCurrentStore();
    if (!$store) {
        return $cache[$key] = null; // регион не определён — гадать, чей квиз показать, нельзя
    }

    $filter = [
        'IBLOCK_ID'                => $iblockId,
        'ACTIVE'                   => 'Y',
        '=PROPERTY_MARQUIZ_REGION' => $store['ID'],
        'CHECK_PERMISSIONS'        => 'N',
    ];
    // Раздел ищем по стабильному якорю XML_ID: символьный код перегенерируется при
    // переименовании раздела в админке (см. include/catalog-sections.php).
    $sectionId = $sectionSlug !== null ? latitudoCatalogSectionId($sectionSlug) : 0;
    if ($sectionId) {
        $filter['=PROPERTY_MARQUIZ_ELEMENT'] = $sectionId;
    } else {
        // Страница без раздела: подходят только элементы с ПУСТОЙ привязкой к разделу,
        // иначе на главную попал бы случайный квиз конкретного лендинга.
        $filter['=PROPERTY_MARQUIZ_ELEMENT'] = false;
    }

    $el = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        $filter,
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME', 'PROPERTY_MARQUIZ_ID', 'PROPERTY_MARQUIZ_TITLE']
    )->Fetch();

    if (!$el) {
        return $cache[$key] = null;
    }

    $quizId = latitudoMarquizSafeId((string)$el['PROPERTY_MARQUIZ_ID_VALUE']);
    if ($quizId === null) {
        return $cache[$key] = null; // поле пустое или заполнено мусором — блок не рисуем
    }

    return $cache[$key] = [
        'id'    => $quizId,
        'title' => trim((string)($el['PROPERTY_MARQUIZ_TITLE_VALUE'] ?? '')),
    ];
}

/**
 * Проверка идентификатора квиза: ровно 24 шестнадцатеричных символа (формат marquiz.ru).
 *
 * ЗАЧЕМ. Значение из админки попадает и в HTML-атрибут, и внутрь <script> — то есть в
 * место, где произвольная строка означала бы возможность выполнить чужой JS на сайте.
 * Строгий формат закрывает это на входе: наружу уходит либо валидный ID, либо ничего.
 * Экранирования в шаблоне для JS-контекста недостаточно, поэтому проверка здесь.
 */
function latitudoMarquizSafeId(string $value): ?string
{
    $value = mb_strtolower(trim($value));

    return preg_match('~^[0-9a-f]{24}$~', $value) ? $value : null;
}

/** Блок с квизом на лендинге раздела — одна строка на странице. */
function latitudoShowMarquizForSection(?string $sectionSlug = null): void
{
    $quiz = latitudoSectionMarquiz($sectionSlug);
    if ($quiz) {
        latitudoShowMarquiz($quiz);
    }
}

/**
 * Загрузчик marquiz.ru — ровно один раз на страницу, при первом выводе квиза.
 * Код взят из кабинета Marquiz как есть; autoOpen/openOnExit выключены, поэтому сам по
 * себе он ничего не показывает — только даёт объект Marquiz для вставок ниже.
 */
function latitudoMarquizLoader(): void
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;
    ?>
    <!-- Marquiz script start -->
    <script>
    (function(w, d, s, o){
      <?php // Схему задаём явно (в коде из кабинета Marquiz было «//»): по протокол-относительной
            // ссылке на странице, открытой по http, скрипт поехал бы тоже по http — с
            // возможностью подмены содержимого по дороге. ?>
      var j = d.createElement(s); j.async = true; j.src = 'https://script.marquiz.ru/v2.js';j.onload = function() {
        if (document.readyState !== 'loading') Marquiz.init(o);
        else document.addEventListener("DOMContentLoaded", function() {
          Marquiz.init(o);
        });
      };
      d.head.insertBefore(j, d.head.firstElementChild);
    })(window, document, 'script', {
        host: '//quiz.marquiz.ru',
        region: 'ru',
        id: '67c02b868b230d00194918ce',
        autoOpen: false,
        autoOpenFreq: 'once',
        openOnExit: false,
        disableOnMobile: false
      }
    );
    </script>
    <!-- Marquiz script end -->
    <?
}

/**
 * Рисует секцию с квизом: необязательный заголовок + контейнер виджета.
 * $quiz — ['id' => валидный ID, 'title' => строка].
 */
function latitudoShowMarquiz(array $quiz): void
{
    $id = latitudoMarquizSafeId((string)($quiz['id'] ?? ''));
    if ($id === null) {
        return; // страховка: сюда могли позвать в обход latitudoSectionMarquiz()
    }
    $title = trim((string)($quiz['title'] ?? ''));

    latitudoMarquizLoader();
    ?>
    <section class="section marquiz" id="marquiz">
        <div class="container">
            <? if ($title !== ''): ?>
            <div class="section__head">
                <h2 class="section__title marquiz__title"><?= htmlspecialcharsbx($title) ?></h2>
            </div>
            <? endif ?>

            <div class="marquiz__widget">
                <? // $id прошёл latitudoMarquizSafeId() — только [0-9a-f]{24}. Экранирование
                   // атрибута тут уже избыточно, но пусть будет: если валидатор когда-нибудь
                   // ослабят (например, разрешат «-» под новый формат ID), атрибут не поедет. ?>
                <div data-marquiz-id="<?= htmlspecialcharsbx($id) ?>"></div>
                <script>(function(t, p) {window.Marquiz ? Marquiz.add([t, p]) : document.addEventListener('marquizLoaded', function() {Marquiz.add([t, p])})})('Inline', {id: '<?= $id ?>', buttonText: '«Старт»', bgColor: '#ffa20c', textColor: '#ffffff', rounded: true, shadow: 'rgba(255, 162, 12, 0.5)', blicked: true, fixed: false, buttonOnMobile: true, disableOnMobile: false, symbolIconId: 'native', symbolMode: 'icon', emojiPack: 'standard', fullWidth: false})</script>
            </div>
        </div>
    </section>
    <?
}
