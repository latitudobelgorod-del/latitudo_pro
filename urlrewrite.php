<?php
$arUrlRewrite=array (
  1 => 
  array (
    'CONDITION' => '#^\\/?\\/mobileapp/jn\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => NULL,
    'PATH' => '/bitrix/services/mobileapp/jn.php',
    'SORT' => 100,
  ),
  3 => 
  array (
    'CONDITION' => '#^/bitrix/services/ymarket/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/bitrix/services/ymarket/index.php',
    'SORT' => 100,
  ),
  0 => 
  array (
    'CONDITION' => '#^/stssync/calendar/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/calendar/index.php',
    'SORT' => 100,
  ),
  2 => 
  array (
    'CONDITION' => '#^/rest/#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/bitrix/services/rest/index.php',
    'SORT' => 100,
  ),
  4 =>
  array (
    'CONDITION' => '#^/news/#',
    'RULE' => '',
    'ID' => 'bitrix:news',
    'PATH' => '/news/index.php',
    'SORT' => 100,
  ),
  // Лендинги разделов каталога: /zabory/, /perila/, любой новый раздел из админки.
  // Папок под них в структуре сайта нет — все обслуживает один диспетчер.
  //
  // ВАЖНО ПРО ПОРЯДОК: движок (bitrix/modules/main/include/urlrewrite.php) идёт по
  // массиву foreach'ем, в порядке объявления, а не по SORT. Правило ловит ЛЮБОЙ
  // односегментный адрес, поэтому должно оставаться ПОСЛЕДНИМ; SORT=200 держит его
  // в конце и после пересохранения правил из админки.
  //
  // Существующие файлы и папки сюда не попадают вовсе: .htaccess отдаёт запрос
  // в bitrix/urlrewrite.php только при !-f, !-d и !-l.
  5 =>
  array (
    'CONDITION' => '#^/[a-z0-9_-]+/?(\\?.*)?$#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/local/routes/catalog-landing.php',
    'SORT' => 200,
  ),
);
