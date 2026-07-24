<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Каталог продукции на главной: оставляем только разделы с галочкой
 * «Показывать раздел на главной странице» (UF_SHOW_ON_MAIN_PAGE) у раздела в админке.
 *
 * Снятая галочка убирает раздел ТОЛЬКО с главной. Его лендинг продолжает работать:
 * /pergoly/ открывается по прямой ссылке, из меню «Все продукты» и из выдачи.
 *
 * Поле приезжает сюда через параметр SECTION_USER_FIELDS компонента (см. index.php).
 * Если поля в базе ещё нет — код выкатили, а tools/setup-landing-blocks.php не прогнали —
 * ключа в выборке не будет вовсе. В этом случае НЕ фильтруем: пустой каталог на главной
 * куда хуже одного лишнего раздела.
 *
 * Фильтруем здесь, а не параметром FILTER_NAME, именно ради этой деградации: фильтр
 * по несуществующему UF-полю отдал бы пустой список молча.
 */
$field = 'UF_SHOW_ON_MAIN_PAGE';

$fieldExists = false;
foreach ($arResult['SECTIONS'] as $arSection) {
    if (array_key_exists($field, $arSection)) {
        $fieldExists = true;
        break;
    }
}

if ($fieldExists) {
    $arResult['SECTIONS'] = array_values(array_filter(
        $arResult['SECTIONS'],
        static function (array $section) use ($field): bool {
            return (string)($section[$field] ?? '') === '1';
        }
    ));
}
