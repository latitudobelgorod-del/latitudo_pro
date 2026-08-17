<?php
/**
 * Выгрузка офлайн-конверсий в Яндекс.Метрику по yclid.
 *
 * Зачем всё это — см. шапку local/php_interface/include/metrika-conversions.php.
 * Коротко: у посетителя без согласия на cookie счётчик не грузится и JS-цель
 * marquiz-finish не срабатывает. Сайт складывает такие заявки в очередь, а этот
 * скрипт раз в час досылает их в Метрику через API.
 *
 * ЗАПУСК:
 *   на проде (крон, раз в час):  php /home/<user>/www/latitudo.pro/tools/metrika-upload-conversions.php
 *   вручную с ноутбука:          ssh regru-latitudo "cd www/latitudo.pro && php tools/metrika-upload-conversions.php"
 *   посмотреть, что уйдёт:       php tools/metrika-upload-conversions.php --dry-run
 *
 * Нужен файл metrika.token в корне сайта — OAuth-токен с доступом к Метрике
 * (oauth.yandex.ru, право «Получение статистики, чтение параметров... и управление»).
 * Нет файла → скрипт ничего не шлёт и говорит об этом; очередь при этом копится
 * и не теряется, после появления токена уедет целиком.
 *
 * ПОЧЕМУ КРОН, А НЕ ОТПРАВКА ПРЯМО ИЗ ФОРМЫ. Обращение к внешнему API в обработчике
 * заявки — это шанс подвесить отправку формы на таймауте сети. Ради статистики так
 * рисковать нельзя: заявка важнее конверсии в отчёте.
 *
 * ПОТЕРЬ ДАННЫХ НЕТ. Очередь не удаляется в момент отправки: она переименовывается
 * в .batch-файл, и тот живёт до подтверждённого ответа API. Упала сеть или API вернул
 * ошибку — батч остаётся на диске и уйдёт следующим запуском.
 */

// Папку tools закрывает .htaccess, но если сервер его не читает (AllowOverride None) —
// эта проверка не даст дёрнуть выгрузку HTTP-запросом. Как в остальных скриптах tools/.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

$dryRun = in_array('--dry-run', $argv ?? [], true);

// В CLI DOCUMENT_ROOT либо пустой, либо отсутствует — берём корень проекта от самого файла.
$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $docRoot;

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
require($docRoot . '/bitrix/modules/main/include/prolog_before.php');

/**
 * Забрать текущую очередь в отдельный батч-файл.
 * Переименование, а не чтение+очистка: пока API отвечает, сайт продолжает принимать
 * заявки и дописывать их в новую очередь — так ни одна не потеряется и не уедет дважды.
 * Блокировка нужна, чтобы не переименовать файл посреди дозаписи строки.
 */
function latitudoMetrikaRotateQueue(): string
{
    $queue = latitudoMetrikaQueuePath();
    if (!is_file($queue) || filesize($queue) === 0) {
        return '';
    }

    $batch = $queue . '.' . date('YmdHis') . '.batch';
    $fp = @fopen($queue, 'c+');
    if (!$fp) {
        return '';
    }
    $ok = false;
    if (flock($fp, LOCK_EX)) {
        $ok = @rename($queue, $batch);
        flock($fp, LOCK_UN);
    }
    fclose($fp);

    return $ok ? $batch : '';
}

/** Все необработанные батчи, старые первыми: порядок отправки = порядок заявок. */
function latitudoMetrikaPendingBatches(): array
{
    $files = glob(latitudoMetrikaQueuePath() . '.*.batch') ?: [];
    sort($files);

    return $files;
}

function out(string $msg): void
{
    echo $msg . "\n";
    latitudoMetrikaLog('CRON ' . $msg);
}

// ─────────────────────────────────────────────────────────────────────────────

$token = latitudoMetrikaToken();
if ($token === '' && !$dryRun) {
    // Не ошибка: на локалке токена нет и быть не должно. Очередь просто ждёт.
    out('SKIP: нет файла metrika.token — выгрузка выключена, очередь копится');
    exit(0);
}

// Сначала подбираем батчи, не уехавшие в прошлые разы, потом добавляем свежую очередь.
$fresh = latitudoMetrikaRotateQueue();
$batches = latitudoMetrikaPendingBatches();

if (!$batches) {
    out('пусто: новых конверсий нет');
    exit(0);
}

$maxAge = LATITUDO_METRIKA_MAX_AGE_DAYS * 24 * 60 * 60;
$now    = time();
$sent   = 0;

foreach ($batches as $batch) {
    $rows = latitudoMetrikaParseQueue((string)file_get_contents($batch));

    // Слишком старые конверсии Метрика уже не к чему прикрепить — визит не сохранился.
    // Отбрасываем их здесь, а не молча: иначе непонятно, куда делись заявки из отчёта.
    $stale = 0;
    $rows = array_values(array_filter($rows, static function (array $r) use ($now, $maxAge, &$stale) {
        if ($now - $r['ts'] > $maxAge) { $stale++; return false; }
        return true;
    }));
    if ($stale > 0) {
        out('WARN ' . basename($batch) . ': ' . $stale . ' конверсий старше '
            . LATITUDO_METRIKA_MAX_AGE_DAYS . ' дней — не отправлены');
    }

    if (!$rows) {
        @unlink($batch);
        continue;
    }

    $csv     = latitudoMetrikaBuildCsv($rows);
    $csvPath = $batch . '.csv';
    file_put_contents($csvPath, $csv);

    if ($dryRun) {
        out('DRY-RUN ' . basename($batch) . ': ' . count($rows) . ' строк');
        echo $csv;
        @unlink($csvPath);
        continue;
    }

    $res = latitudoMetrikaUploadCsv($csvPath, $token, LATITUDO_METRIKA_COUNTER);

    // Два повтора на расхождения в справке Метрики — оба одноразовые, петли нет.
    // 1) client_id_type: в одном разделе документации это query-параметр запроса,
    //    в другом — только поле ответа, а тип берётся из имени колонки CSV.
    if (!$res['ok'] && stripos($res['body'], 'client_id_type') !== false) {
        out('RETRY с client_id_type=YCLID (API попросил): ' . mb_substr($res['body'], 0, 200));
        $res = latitudoMetrikaUploadCsv($csvPath, $token, LATITUDO_METRIKA_COUNTER, true);
    }
    // 2) схема авторизации: русская справка говорит OAuth, английская — Bearer.
    if (!$res['ok'] && in_array($res['status'], [401, 403], true)) {
        out('RETRY с Authorization: Bearer (OAuth дал ' . $res['status'] . ')');
        $res = latitudoMetrikaUploadCsv($csvPath, $token, LATITUDO_METRIKA_COUNTER, false, 'Bearer');
    }

    if ($res['ok']) {
        $sent += count($rows);
        out('OK ' . basename($batch) . ': отправлено ' . count($rows)
            . ' — ' . mb_substr($res['body'], 0, 300));
        @unlink($batch);
        @unlink($csvPath);
    } else {
        // Батч НЕ удаляем — уедет следующим запуском. И прекращаем: если API отвечает
        // ошибкой, остальные батчи получат ту же ошибку, незачем долбить его подряд.
        out('FAIL ' . basename($batch) . ' http=' . $res['status']
            . ' — ' . mb_substr($res['body'], 0, 500));
        @unlink($csvPath);
        exit(1);
    }
}

out('готово: отправлено конверсий — ' . $sent);
exit(0);
