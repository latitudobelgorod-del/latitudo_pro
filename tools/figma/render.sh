#!/usr/bin/env bash
# Рендерит узлы Figma в картинки ОДНИМ Tier-1 запросом (можно много id сразу),
# затем качает готовые файлы с S3 (скачивание с S3 лимит Figma НЕ тратит).
#
# Запуск:
#   bash tools/figma/render.sh svg 389:15379 389:15380          # иконки → .figma-cache/assets/
#   bash tools/figma/render.sh png 2 389:11470 389:11524        # png @2x несколько фреймов
#
# Аргументы: <format: svg|png> [scale (только для png, по умолчанию 2)] <id…>
set -euo pipefail
cd "$(dirname "$0")/../.."

KEY="${FIGMA_KEY:-hswIkVyGSXLn1wXaJ7w0Ps}"
TOKEN=$(tr -d ' \r\n' < figma.token)
FMT="$1"; shift
SCALE=2
if [ "$FMT" = "png" ]; then
  case "${1:-}" in [0-9]*) SCALE="$1"; shift;; esac
fi
IDS=$(IFS=,; echo "$*")
mkdir -p .figma-cache/assets

echo "→ Рендер ($FMT, scale=$SCALE): $IDS"
RESP=$(curl -s -H "X-Figma-Token: $TOKEN" \
  "https://api.figma.com/v1/images/$KEY?ids=$IDS&format=$FMT&scale=$SCALE")

echo "$RESP" | PYTHONUTF8=1 python -c "
import sys,json,urllib.request,re
d=json.load(sys.stdin)
if d.get('err') or 'images' not in d:
    print('✗',d); raise SystemExit(1)
for nid,url in d['images'].items():
    if not url:
        print('  пропуск (нет рендера):',nid); continue
    fn='.figma-cache/assets/'+re.sub(r'[^0-9A-Za-z]+','_',nid)+'.$FMT'
    urllib.request.urlretrieve(url,fn)   # S3 — лимит не тратит
    print('  ✓',fn)
"
echo "Файлы в .figma-cache/assets/ (готовые SVG/PNG скопируй в шаблон по месту)."
