<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Ярлыки карточки товара: «Бесплатная доставка», «Гарантия N лет», «В наличии».
 *
 * Контент — в свойствах элементов «Каталога продукции» (ID=3), заводятся скриптом
 * tools/setup-catalog-badges.php:
 *   GARANTY       — список: «Гарантия 15 лет» / «Гарантия 25 лет» (слово «Гарантия» уже
 *                   внутри значения, поэтому выводим его как есть, ничего не дописывая);
 *   FREE_DOSTAVKA — список «да»/«нет»;
 *   IN_STOCK      — список «да»/«нет».
 * Свойство пустое или «нет» → ярлыка просто нет, пустого места не остаётся.
 *
 * ЗАЧЕМ ОТДЕЛЬНЫЙ ФАЙЛ. Карточка товара свёрстана в ДВУХ шаблонах (catalog.section для
 * шести лендингов и news.list для /catalog/). Разметка ярлыков живёт здесь в одном
 * экземпляре, иначе при любой правке две копии разъезжаются — так уже было с карточкой.
 *
 * Макет: Figma 537:22961 (Product Card, раунд 4).
 */

/**
 * Значение свойства = «да»?
 *
 * Свойство заведено списком со значениями «да»/«нет», но тип легко поменять в админке
 * на «Да/Нет» (тогда придёт 'Y') или на число (придёт '1'). Проверяем все ходовые
 * варианты, чтобы смена типа свойства не гасила ярлыки молча.
 */
function latitudoPropIsYes($value): bool
{
    if (is_array($value)) {
        $value = $value['VALUE'] ?? '';
    }
    $value = mb_strtolower(trim((string)$value));

    return in_array($value, ['да', 'yes', 'y', '1', 'true'], true);
}

/**
 * Свойства элемента → нормализованные данные ярлыков.
 * $props — массив вида $el->GetProperties() либо $arItem['PROPERTIES'].
 */
function latitudoProductBadges(array $props): array
{
    $warranty = $props['GARANTY']['VALUE'] ?? '';
    if (is_array($warranty)) {
        $warranty = reset($warranty) ?: '';
    }

    return [
        'warranty'      => trim((string)$warranty),
        'free_delivery' => latitudoPropIsYes($props['FREE_DOSTAVKA']['VALUE'] ?? ''),
        'in_stock'      => latitudoPropIsYes($props['IN_STOCK']['VALUE'] ?? ''),
    ];
}

/**
 * Ярлыки поверх фото — правый верхний угол картинки (Figma: top 12, right 16, зазор 10).
 * Порядок как в макете: зелёная «Бесплатная доставка», затем белая «Гарантия».
 */
function latitudoRenderProductBadges(array $badges): void
{
    if (!$badges['free_delivery'] && $badges['warranty'] === '') {
        return; // ни одного ярлыка — контейнер не рисуем совсем
    }
    ?>
    <div class="product-card__badges">
        <? if ($badges['free_delivery']): ?>
        <span class="product-badge product-badge--delivery">Бесплатная доставка</span>
        <? endif ?>
        <? if ($badges['warranty'] !== ''): ?>
        <span class="product-badge"><?= htmlspecialcharsbx($badges['warranty']) ?></span>
        <? endif ?>
    </div>
    <?
}

/** Отметка «В наличии» — справа в строке с ценами (Figma: зелёная точка 10px + текст). */
function latitudoRenderProductStock(array $badges): void
{
    if (!$badges['in_stock']) {
        return;
    }
    ?>
    <span class="product-card__stock">
        <? // Точка декоративная: смысл несёт текст рядом, дублировать его скринридеру не надо ?>
        <i class="product-card__stock-dot" aria-hidden="true"></i>В наличии
    </span>
    <?
}
