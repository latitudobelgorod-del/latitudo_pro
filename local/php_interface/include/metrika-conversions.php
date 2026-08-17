<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Офлайн-конверсии Яндекс.Метрики по yclid.
 *
 * ЗАЧЕМ. Счётчик Метрики грузится только после согласия на cookie (см. header.php),
 * поэтому у посетителя без согласия window.ym не существует, JS-цель marquiz-finish
 * не срабатывает, и заявка в статистику не попадает вовсе. Cookieless-режима у Метрики
 * нет — проверено по документации, в параметрах init есть только webvisor/clickmap/
 * trackLinks/accurateTrackBounce. Значит единственный способ показать такую конверсию —
 * досылать её сервером через API офлайн-конверсий, привязывая к yclid.
 *
 * yclid — идентификатор клика по объявлению Яндекс.Директа, приходит параметром в
 * адресе страницы (?yclid=…). Это НЕ cookie Метрики: мы читаем его из URL и храним
 * у себя, ровно как уже храним utm-метки. Поэтому механизм работает независимо от
 * того, загрузился счётчик или нет.
 *
 * ── КОГО ДОСЫЛАЕМ (latitudoMetrikaShouldQueue) ────────────────────────────────
 *   кука пустая (не выбрал) → ДА. Счётчика не было, цель потеряна — её и восполняем.
 *   кука '1' (согласие)     → НЕТ. Счётчик отработал, JS-цель ушла. Дубль конверсии.
 *   кука '0' (отказ)        → НЕТ. Человек явно отказался от аналитики; обходить его
 *                             выбор сервером — то же самое, что кнопка-обманка,
 *                             ровно за это аудит и выписал замечание.
 * Переключатель на случай смены политики — LATITUDO_METRIKA_QUEUE_ON_DECLINE.
 *
 * ── ЧТО НЕ ПОКРЫВАЕТСЯ ────────────────────────────────────────────────────────
 * Только Директ: у органики, соцсетей и прямых заходов yclid нет вовсе, досылать
 * нечего. И только форма заявки: заявки через виджет Envybox приходят к нам не
 * HTTP-запросом на сайт, серверного события для них нет.
 *
 * ── КАК УСТРОЕНО ──────────────────────────────────────────────────────────────
 * 1. Фронт (request-form.php) ловит yclid из URL, хранит его как utm-метки и кладёт
 *    в скрытые поля b24_yclid / b24_consent перед отправкой формы.
 * 2. Здесь — обработчик события FEEDBACK_FORM (то же событие, на котором создаётся
 *    лид в Б24): дописывает строку в очередь local/logs/metrika-queue.jsonl.
 *    В веб-запросе НИКАКИХ обращений к API: сеть в обработчике формы — это риск
 *    подвесить отправку заявки, ради статистики так делать нельзя.
 * 3. Выгрузку делает CLI-скрипт tools/metrika-upload-conversions.php по крону.
 *
 * СЕКРЕТ. OAuth-токен Метрики — в файле metrika.token в корне сайта. Закрыт от HTTP
 * правилом .htaccess (FilesMatch \.token$) и от git (*.token в .gitignore), как
 * figma.token. Нет файла → выгрузка молча спит, очередь копится и не теряется.
 */

// Счётчик Метрики. В header.php и cookie-banner.php он записан числом прямо в JS —
// там это код самой Метрики, который копируется из кабинета целиком, и разбирать его
// на переменные означало бы расходиться с тем, что выдаёт сервис.
const LATITUDO_METRIKA_COUNTER = 110963911;

// Цель. Тот же идентификатор, что шлёт JS (ym(…, 'reachGoal', 'marquiz-finish')):
// конверсии должны собираться в одном отчёте, а не в двух половинках. В Метрике это
// цель типа «JavaScript-событие» — офлайн-конверсии привязываются именно к таким.
const LATITUDO_METRIKA_GOAL = 'marquiz-finish';

// Досылать ли конверсии тех, кто нажал «Отклонить». По умолчанию НЕТ — см. шапку.
const LATITUDO_METRIKA_QUEUE_ON_DECLINE = false;

// Страховка по возрасту конверсии. Метрика требует лишь чтобы DateTime был в прошлом,
// но привязать конверсию она может только к сохранившемуся визиту, а он живёт не вечно.
// Строки старше этого срока не отправляем: они всё равно не прикрепятся, а ошибка на
// них уронила бы весь батч. Значение с запасом.
const LATITUDO_METRIKA_MAX_AGE_DAYS = 20;

/** Папка очереди и логов интеграции. Закрыта от HTTP файлом local/logs/.htaccess. */
function latitudoMetrikaQueueDir(): string
{
    return $_SERVER['DOCUMENT_ROOT'] . '/local/logs';
}

/** Файл очереди: по строке JSON на конверсию (JSON Lines). */
function latitudoMetrikaQueuePath(): string
{
    return latitudoMetrikaQueueDir() . '/metrika-queue.jsonl';
}

/** Запись в лог интеграции. Тихо, без падений — как в b24-lead.php. */
function latitudoMetrikaLog(string $msg): void
{
    $dir = latitudoMetrikaQueueDir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($dir . '/metrika-conv.log', date('c') . ' ' . $msg . "\n", FILE_APPEND);
}

/**
 * Ставить ли конверсию в очередь при таком состоянии куки согласия.
 * Чистая функция — тестируется офлайн. Значения: '' (не выбрал), '1', '0'.
 */
function latitudoMetrikaShouldQueue(string $consent): bool
{
    if ($consent === '1') {
        return false; // счётчик был, JS-цель уже отправлена
    }
    if ($consent === '0') {
        return LATITUDO_METRIKA_QUEUE_ON_DECLINE;
    }

    return true; // выбора не делали — счётчика не было, цель потеряна
}

/**
 * Дописать конверсию в очередь. Никогда не бросает наружу: отправка заявки важнее
 * статистики, любая проблема с файлом — только в лог.
 *
 * LOCK_EX на дозаписи обязателен: несколько заявок могут прийти одновременно, и без
 * блокировки строки перемешались бы посреди друг друга.
 */
function latitudoMetrikaQueueRow(string $yclid, int $ts): void
{
    try {
        $dir = latitudoMetrikaQueueDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        // Обычный json_encode, а не Bitrix\Main\Web\Json: значения тут строго ASCII
        // (yclid и наш идентификатор цели), зато модуль остаётся проверяемым офлайн,
        // без поднятия ядра Битрикса.
        $line = json_encode(['yclid' => $yclid, 'target' => LATITUDO_METRIKA_GOAL, 'ts' => $ts]);
        @file_put_contents(latitudoMetrikaQueuePath(), $line . "\n", FILE_APPEND | LOCK_EX);
    } catch (\Throwable $e) {
        latitudoMetrikaLog('QUEUE EXCEPTION ' . $e->getMessage());
    }
}

/**
 * Разбор данных формы и постановка в очередь.
 * Отдельно от latitudoMetrikaQueueRow(), чтобы правила («кого досылаем») можно было
 * проверить, не трогая файловую систему.
 */
function latitudoMetrikaQueueFromPost(array $post): void
{
    $yclid = trim((string)($post['b24_yclid'] ?? ''));
    if ($yclid === '') {
        return; // заход не из Директа — привязывать конверсию не к чему
    }

    $consent = trim((string)($post['b24_consent'] ?? ''));
    if (!latitudoMetrikaShouldQueue($consent)) {
        return;
    }

    latitudoMetrikaQueueRow($yclid, time());
    latitudoMetrikaLog('QUEUED yclid=' . $yclid . ' consent=' . ($consent === '' ? 'none' : $consent));
}

/**
 * Разбор файла очереди в массив строк. Чистая функция — тестируется офлайн.
 * Битые строки пропускаем молча: одна повреждённая запись не должна блокировать
 * выгрузку остальных.
 */
function latitudoMetrikaParseQueue(string $raw): array
{
    $rows = [];
    foreach (preg_split('~\r\n|\n|\r~', $raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $row = json_decode($line, true);
        if (!is_array($row) || empty($row['yclid']) || empty($row['ts'])) {
            continue;
        }
        $rows[] = [
            'yclid'  => (string)$row['yclid'],
            'target' => (string)($row['target'] ?? LATITUDO_METRIKA_GOAL),
            'ts'     => (int)$row['ts'],
        ];
    }

    return $rows;
}

/**
 * CSV для API. Чистая функция — тестируется офлайн.
 * Колонки строго по документации: Yclid, Target, DateTime (unix timestamp).
 * Именно имя колонки Yclid говорит Метрике, какой это тип идентификатора.
 */
function latitudoMetrikaBuildCsv(array $rows): string
{
    $csv = "Yclid,Target,DateTime\n";
    foreach ($rows as $row) {
        // Экранирование не нужно и вредно: yclid — [0-9a-zA-Z_-], цель — наш
        // собственный идентификатор без запятых. Значения с запятой отбрасываем,
        // чтобы кривая строка не сдвинула колонки во всём файле.
        if (strpbrk($row['yclid'] . $row['target'], ",\"\r\n") !== false) {
            continue;
        }
        $csv .= $row['yclid'] . ',' . $row['target'] . ',' . $row['ts'] . "\n";
    }

    return $csv;
}

/** OAuth-токен Метрики из metrika.token в корне. '' — файла нет (выгрузка выключена). */
function latitudoMetrikaToken(): string
{
    $file = $_SERVER['DOCUMENT_ROOT'] . '/metrika.token';

    return is_readable($file) ? trim((string)file_get_contents($file)) : '';
}

/**
 * Отправка готового CSV-файла в API офлайн-конверсий.
 * Возвращает ['ok' => bool, 'status' => int, 'body' => string].
 *
 * $withClientIdType — запасной путь. Документация Метрики противоречива: в одном
 * месте client_id_type описан как query-параметр запроса, в другом — только как поле
 * ОТВЕТА, а тип определяется именем колонки в CSV. Поэтому сначала шлём без
 * параметра (как в актуальной справке), и лишь если API ругнётся именно на него —
 * повторяем с ним. Гадать заранее нельзя, а молча получать 400 — тем более.
 *
 * $authScheme — та же история: русская справка показывает «Authorization: OAuth <токен>»,
 * английская — «Bearer». Схема по умолчанию OAuth (родная для Яндекса), повтор с Bearer
 * делает вызывающий код при 401/403. Молча висеть месяц со сломанной авторизацией
 * крону нельзя: его никто не смотрит, пока не хватятся цифр.
 *
 * cURL напрямую, а не Bitrix HttpClient: нужен multipart/form-data с файлом,
 * HttpClient его не умеет без ручной сборки тела.
 */
function latitudoMetrikaUploadCsv(
    string $csvPath,
    string $token,
    int $counterId,
    bool $withClientIdType = false,
    string $authScheme = 'OAuth'
): array {
    $url = 'https://api-metrika.yandex.net/management/v1/counter/' . $counterId
         . '/offline_conversions/upload';
    if ($withClientIdType) {
        $url .= '?client_id_type=YCLID';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['file' => new CURLFile($csvPath, 'text/csv', 'conversions.csv')],
        CURLOPT_HTTPHEADER     => ['Authorization: ' . $authScheme . ' ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $body   = (string)curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($err !== '') {
        $body = 'curl: ' . $err;
    }

    return ['ok' => $status === 200, 'status' => $status, 'body' => $body];
}

/**
 * Обработчик отправки формы. Вешаемся на то же событие FEEDBACK_FORM, что и лид в Б24
 * (см. b24-lead.php), но отдельным обработчиком: очередь Метрики и CRM — разные задачи,
 * и падение одной не должно задевать другую. Битрикс вызовет оба.
 */
function latitudoMetrikaOnBeforeEventAdd($event, $lid, &$arFields)
{
    if ($event === 'FEEDBACK_FORM') {
        static $done = false;
        if (!$done) {
            $done = true;
            latitudoMetrikaQueueFromPost($_POST);
        }
    }

    return true; // письмо отправляется как обычно
}

AddEventHandler('main', 'OnBeforeEventAdd', 'latitudoMetrikaOnBeforeEventAdd');
