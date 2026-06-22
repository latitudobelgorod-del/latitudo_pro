# Figma — рабочий процесс «кэш-сначала» (обход rate-limit)

## Зачем
Лимит Figma REST API **зависит от места (seat) в команде**, а не только от плана:

| Seat | Tier-1 (`GET file`, `GET file nodes`, `GET image`) |
|---|---|
| **View / Collab (наш сейчас, `low`)** | **≈ 6 запросов в МЕСЯЦ** |
| Dev / Full (`high`), Starter | 10 запросов / мин |
| Dev / Full, Professional | 15 / мин |

Проверить свой seat: ответ 429 содержит `X-Figma-Rate-Limit-Type` (`low`=View, `high`=Dev/Full),
`X-Figma-Plan-Tier` и `Retry-After` (секунд до сброса).

Вывод: на View-seat **нельзя** дёргать API поузельно. Правило — **один запрос на весь файл,
дальше работаем оффлайн по кэшу**. Иконки/фото — выгружать из UI Figma (API не тратить).

## Команды

```bash
# 1) Один раз (или когда дизайн изменился) — скачать ВЕСЬ файл в .figma-cache/ (1 Tier-1 запрос)
bash tools/figma/pull.sh

# 2) Инспекция — ОФФЛАЙН, без API, сколько угодно раз:
PYTHONUTF8=1 python tools/figma/inspect.py find "Каталог"      # найти узлы по имени → id
PYTHONUTF8=1 python tools/figma/inspect.py node 389:11558      # свойства узла + детей
PYTHONUTF8=1 python tools/figma/inspect.py tree 389:11558 5    # дерево с размерами
PYTHONUTF8=1 python tools/figma/inspect.py text 389:11536      # типографика всех текстов
PYTHONUTF8=1 python tools/figma/inspect.py css  389:11558      # шпаргалка-CSS узла

# 3) Ассеты (когда есть бюджет Tier-1): один запрос рендерит много узлов, качаем с S3
bash tools/figma/render.sh svg 389:15379 389:15380             # иконки → .figma-cache/assets/
bash tools/figma/render.sh png 2 389:11470                     # png @2x
```

`.figma-cache/` в `.gitignore` — кэш и ассеты в репозиторий не попадают, токен тоже.

## Если бюджет API исчерпан (View-seat, ждать сброса ~дни)
Без API, прямо из приложения Figma (бесплатно):
- **Экспорт PNG@2x** нужных фреймов + **SVG** иконок → положить в `.figma-cache/assets/`.
- **Плагин экспорта в JSON** (напр. «Figma to JSON»/«Design Tokens») в форме REST-узлов —
  сохранить как `.figma-cache/file.json`, и `inspect.py` будет с ним работать.
- Точные числа — из панели Inspect (W/H, цвета, паддинги) скопировать в задачу.

## Лучшее долгосрочное решение
- **+1 Dev/Full seat** в аккаунте (план можно оставить Starter) → лимит `high` (10/мин) →
  `pull.sh` кэширует всё одним запросом, поузельные запросы больше не нужны.
- Либо **Figma Dev Mode MCP server** (бесплатно в бете, нужен Dev-доступ): подключается
  к Claude Code по MCP и отдаёт живые CSS/переменные/размеры выбранного узла без REST-лимита.
