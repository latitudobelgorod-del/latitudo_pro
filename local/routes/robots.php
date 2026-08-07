<?php
/**
 * robots.txt — отдаётся кодом, файла на диске нет.
 *
 * ЗАЧЕМ НЕ ФАЙЛ. Из-за одной-единственной строки — Sitemap. Она обязана быть
 * абсолютной и указывать на ТОТ ЖЕ хост, с которого robots.txt прочитан: карту
 * на чужом хосте Яндекс не принимает. А docroot у голого домена и у пяти
 * поддоменов филиалов общий — статический файл отдавал бы всем один и тот же
 * адрес, и для пяти филиалов из шести он был бы чужим.
 *
 * Правила ниже — те же, что лежали в robots.txt, слово в слово. Меняются здесь,
 * в Git; в админке этот файл не правится (раньше тоже не правился — он был в Git).
 *
 * КАК ПОПАДАЕМ СЮДА. Правило в urlrewrite.php. Файла /robots.txt на диске нет,
 * поэтому .htaccess отдаёт запрос роутеру Битрикса — как и любой другой адрес,
 * которому не соответствует файл. Если файл когда-нибудь появится в корне,
 * Apache начнёт отдавать его и этот роут молча перестанет работать.
 */

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

// Канонический адрес текущего города: голый домен для Москвы, rostov. для Ростова,
// www. отброшен. Та же функция строит ссылки переключателю городов и карте сайта.
$base = rtrim(latitudoCityUrl(latitudoCurrentRegionCode()), '/');

$rules = <<<ROBOTS
User-agent: *
Disallow: /bitrix/
Disallow: /search/
Allow: /search/map
Allow: /search/map.php
Disallow: /svyazannye-tovary/
Disallow: /auth/
Disallow: /auth.php
Disallow: /*?print=
Disallow: /*&print=
Disallow: /*register=yes
Disallow: /*forgot_password=yes
Disallow: /*change_password=yes
Disallow: /*login=yes
Disallow: /*logout=yes
Disallow: /*auth=yes
Disallow: /*backurl=*
Disallow: /*BACKURL=*
Disallow: /*back_url=*
Disallow: /*BACK_URL=*
Disallow: /*back_url_admin=*
Disallow: /*index.php\$
ROBOTS;

header('Content-Type: text/plain; charset=UTF-8');

echo $rules . "\n\nSitemap: " . $base . "/sitemap.xml\n";

// Пролог без эпилога — по той же причине, что и в sitemap.php: эпилог дорисовал бы
// страницу, а здесь ответ должен быть чистым текстом.
die();
