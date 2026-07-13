# Figma — рабочий процесс «кэш-сначала»

## Текущий доступ
Файл-макет `(ФИНАЛ) Лендо-сайт Latitudo` (`hswIkVyGSXLn1wXaJ7w0Ps`) — **наша копия
в Professional-команде**, где у нас **Full/Editor seat**. Лимит Figma REST API зависит именно
от места (seat) в команде, где лежит файл, а не только от тарифа:

| Seat | Tier-1 (`GET file`, `GET file nodes`, `GET image`) |
|---|---|
| View / Collab (`low`) | ≈ 6 запросов в МЕСЯЦ — так было раньше |
| Dev / Full (`high`), Starter | 10 запросов / мин |
| **Dev / Full, Professional (наш сейчас)** | **15 / мин** |

Проверить seat: ответ 429 содержит `X-Figma-Rate-Limit-Type` (`low`=View, `high`=Dev/Full),
`X-Figma-Plan-Tier` и `Retry-After` (секунд до сброса).

Поузельные запросы и рендер иконок теперь разрешены. Но файл весит **162 МБ**, поэтому рабочий
режим остаётся прежним — **один запрос на весь файл, дальше оффлайн по кэшу**.

## Команды

```bash
# 1) Один раз (или когда дизайн изменился) — скачать ВЕСЬ файл в .figma-cache/ (1 Tier-1 запрос)
bash tools/figma/pull.sh

# 2) Инспекция — ОФФЛАЙН, без API, сколько угодно раз:
PYTHONUTF8=1 python tools/figma/inspect.py find "Каталог"      # найти узлы по имени → id
PYTHONUTF8=1 python tools/figma/inspect.py node 537:19091      # свойства узла + детей
PYTHONUTF8=1 python tools/figma/inspect.py tree 537:19091 5    # дерево с размерами
PYTHONUTF8=1 python tools/figma/inspect.py text 537:21996      # типографика всех текстов
PYTHONUTF8=1 python tools/figma/inspect.py css  537:19091      # шпаргалка-CSS узла

# 3) Ассеты: один запрос рендерит много узлов, качаем с S3
bash tools/figma/render.sh svg 537:23158                       # иконки → .figma-cache/assets/
bash tools/figma/render.sh png 2 537:19091                     # png @2x
```

⚠️ Узлы **раунда 4** (`537:…`) — актуальные. Узлы `389:…` — это раунд 2, устаревший.

`.figma-cache/` в `.gitignore` — кэш и ассеты в репозиторий не попадают, токен тоже.

## Если дизайнеры пришлют новый файл
1. Дублировать их файл к себе (Duplicate) и перенести копию в проект **своей Professional-команды**
   — иначе seat будет View и лимит упадёт до 6 запросов в месяц.
2. Взять новый ключ из URL, подставить: `FIGMA_KEY=<новый> bash tools/figma/pull.sh`,
   затем прописать его по умолчанию в `pull.sh` / `render.sh` и в `CLAUDE.md`.
3. Проверить токен (лимит не тратит): `curl -H "X-Figma-Token: $TOKEN" https://api.figma.com/v1/me`.

## Возможное улучшение
**Figma Dev Mode MCP server** (нужен Dev/Full seat — он у нас есть): подключается к Claude Code
по MCP и отдаёт живые CSS/переменные/размеры выделенного узла вообще без REST-лимита и без
162-мегабайтного кэша.
