#!/usr/bin/env bash
# Скачивает ВЕСЬ файл Figma одним Tier-1 запросом в локальный кэш .figma-cache/file.json.
# Дальше инспектируем оффлайн через inspect.py — без обращений к API и без rate-limit.
# Перезапускать ТОЛЬКО когда дизайн в Figma реально поменялся.
#
# Запуск:  bash tools/figma/pull.sh
set -euo pipefail
cd "$(dirname "$0")/../.."

KEY="${FIGMA_KEY:-tMnmSKvkGJvSiZSo7ft2zV}"
TOKEN=$(tr -d ' \r\n' < figma.token)
mkdir -p .figma-cache

echo "→ Тяну весь файл $KEY (1 запрос)…"
HTTP=$(curl -s -w '%{http_code}' -H "X-Figma-Token: $TOKEN" \
  "https://api.figma.com/v1/files/$KEY" -o .figma-cache/file.json)

if [ "$HTTP" = "429" ]; then
  echo "✗ 429 Rate limit. Подожди ~60 c (лимит Tier-1 ≈ 10 запросов/мин) и повтори."; exit 1
fi
if [ "$HTTP" != "200" ]; then
  echo "✗ HTTP $HTTP:"; cat .figma-cache/file.json; echo; exit 1
fi

PYTHONUTF8=1 python -c "
import json,os
d=json.load(open('.figma-cache/file.json',encoding='utf-8'))
def c(n):
    s=1
    for ch in n.get('children',[]): s+=c(ch)
    return s
print('✓ %s | изменён %s | страниц %d | узлов %d | %.1f МБ'%(
    d.get('name'), d.get('lastModified'), len(d['document']['children']),
    c(d['document']), os.path.getsize('.figma-cache/file.json')/1e6))
"
echo "Готово. Инспекция: PYTHONUTF8=1 python tools/figma/inspect.py find \"Каталог\""
