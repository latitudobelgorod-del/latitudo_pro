#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Оффлайн-инспектор макета Figma для проекта Latitudo.

Работает по ЛОКАЛЬНОМУ кэшу (.figma-cache/file.json), который скачивается один раз
через tools/figma/pull.sh. Никаких обращений к Figma API при инспекции — значит
никакого rate-limit. Пересобирать кэш только когда дизайн поменялся.

Запуск (Windows, Git Bash):  PYTHONUTF8=1 python tools/figma/inspect.py <команда> [аргументы]

Команды:
  find <подстрока>            список узлов, в имени которых есть подстрока (id + размер)
  node <id>                   ключевые свойства узла + его прямых детей
  tree <id> [глубина]         дерево имён/типов/размеров (по умолчанию глубина 4)
  text <id>                   все текстовые узлы внутри: шрифт/размер/межстрочный/цвет/текст
  css  <id>                   приблизительный CSS узла (фон, радиус, паддинги, шрифт) — шпаргалка

Пример:
  PYTHONUTF8=1 python tools/figma/inspect.py find "Каталог"
  PYTHONUTF8=1 python tools/figma/inspect.py css 389:11558
"""
import json
import os
import sys

CACHE = os.path.join(os.path.dirname(__file__), "..", "..", ".figma-cache", "file.json")


def load():
    if not os.path.exists(CACHE):
        sys.exit("Нет кэша %s — сначала запусти tools/figma/pull.sh" % CACHE)
    with open(CACHE, encoding="utf-8") as f:
        d = json.load(f)
    # поддержка двух форматов: /v1/files (document) и /v1/files/nodes (nodes{})
    if "document" in d:
        roots = [d["document"]]
    elif "nodes" in d:
        roots = [v["document"] for v in d["nodes"].values()]
    else:
        sys.exit("Неожиданный формат кэша: %s" % list(d.keys()))
    return roots


def index(roots):
    by_id = {}

    def walk(n):
        by_id[n["id"]] = n
        for ch in n.get("children", []):
            walk(ch)

    for r in roots:
        walk(r)
    return by_id


def rgba(c, opacity=None):
    r = round(c.get("r", 0) * 255)
    g = round(c.get("g", 0) * 255)
    b = round(c.get("b", 0) * 255)
    a = c.get("a", 1)
    if opacity is not None:
        a = opacity
    if a >= 0.999:
        return "#%02X%02X%02X" % (r, g, b)
    return "rgba(%d,%d,%d,%.2f)" % (r, g, b, a)


def fill_str(node):
    out = []
    for f in node.get("fills", []) or []:
        if f.get("visible") is False:
            continue
        t = f.get("type")
        if t == "SOLID":
            out.append(rgba(f["color"], f.get("opacity")))
        elif t and t.startswith("GRADIENT"):
            stops = ",".join(rgba(s["color"]) for s in f.get("gradientStops", []))
            out.append("%s(%s)" % (t.lower(), stops))
        elif t == "IMAGE":
            out.append("image")
    return ", ".join(out) if out else "—"


def size(node):
    bb = node.get("absoluteBoundingBox") or {}
    return "%sx%s" % (round(bb.get("width", 0)), round(bb.get("height", 0)))


def radius(node):
    if "rectangleCornerRadii" in node:
        return "/".join(str(round(x)) for x in node["rectangleCornerRadii"])
    if "cornerRadius" in node:
        return str(round(node["cornerRadius"]))
    return None


def layout(node):
    bits = []
    if node.get("layoutMode"):
        bits.append("auto:" + node["layoutMode"])
    pads = [node.get(k) for k in ("paddingTop", "paddingRight", "paddingBottom", "paddingLeft")]
    if any(p for p in pads):
        bits.append("pad %s" % "/".join(str(round(p or 0)) for p in pads))
    if node.get("itemSpacing"):
        bits.append("gap %s" % round(node["itemSpacing"]))
    return " ".join(bits)


def effects(node):
    out = []
    for e in node.get("effects", []) or []:
        if e.get("visible") is False:
            continue
        if e.get("type") in ("DROP_SHADOW", "INNER_SHADOW"):
            o = e.get("offset", {})
            out.append(
                "%s %d %d %d %s"
                % (
                    "shadow" if e["type"] == "DROP_SHADOW" else "inner",
                    round(o.get("x", 0)),
                    round(o.get("y", 0)),
                    round(e.get("radius", 0)),
                    rgba(e.get("color", {})),
                )
            )
    return ", ".join(out) if out else None


def typo(node):
    s = node.get("style", {})
    if not s:
        return None
    lh = s.get("lineHeightPx")
    lh = "%dpx" % round(lh) if lh else (str(s.get("lineHeightPercent")) + "%" if s.get("lineHeightPercent") else "auto")
    return "%s %s/%s lh=%s ls=%s case=%s align=%s color=%s" % (
        s.get("fontFamily", "?"),
        s.get("fontWeight", "?"),
        round(s.get("fontSize", 0)),
        lh,
        round(s.get("letterSpacing", 0), 2),
        s.get("textCase", "—"),
        s.get("textAlignHorizontal", "—"),
        fill_str(node),
    )


def line(node, depth=0):
    bits = ["%s%s [%s] %s %s" % ("  " * depth, node.get("name", "?"), node.get("type"), node["id"], size(node))]
    r = radius(node)
    if r:
        bits.append("r=" + r)
    lay = layout(node)
    if lay:
        bits.append(lay)
    if node.get("type") in ("RECTANGLE", "FRAME", "INSTANCE", "COMPONENT", "ELLIPSE"):
        bits.append("fill=" + fill_str(node))
    ef = effects(node)
    if ef:
        bits.append("[" + ef + "]")
    if node.get("type") == "TEXT":
        bits.append("« %s »" % node.get("characters", "")[:40].replace("\n", " "))
        t = typo(node)
        if t:
            bits.append("\n      " + t)
    return "  ".join(bits)


def cmd_find(by_id, sub):
    sub = sub.lower()
    for nid, n in by_id.items():
        if sub in n.get("name", "").lower():
            print("%-14s %-10s %-8s %s" % (nid, n.get("type"), size(n), n.get("name")))


def cmd_node(by_id, nid):
    n = by_id.get(nid)
    if not n:
        sys.exit("нет узла " + nid)
    print(line(n))
    for ch in n.get("children", []):
        print("  " + line(ch))


def cmd_tree(by_id, nid, maxd=4):
    n = by_id.get(nid)
    if not n:
        sys.exit("нет узла " + nid)

    def walk(x, d):
        print(line(x, d))
        if d < maxd:
            for ch in x.get("children", []):
                walk(ch, d + 1)

    walk(n, 0)


def cmd_text(by_id, nid):
    n = by_id.get(nid)
    if not n:
        sys.exit("нет узла " + nid)

    def walk(x):
        if x.get("type") == "TEXT":
            print("«%s»" % x.get("characters", "").replace("\n", " ")[:60])
            print("   ", typo(x), " ", size(x), x["id"])
        for ch in x.get("children", []):
            walk(ch)

    walk(n)


def cmd_css(by_id, nid):
    n = by_id.get(nid)
    if not n:
        sys.exit("нет узла " + nid)
    print("/* %s [%s] %s %s */" % (n.get("name"), n.get("type"), n["id"], size(n)))
    print("{")
    if n.get("type") != "TEXT":
        print("  background: %s;" % fill_str(n))
    r = radius(n)
    if r:
        if "/" in r:
            print("  border-radius: %spx;" % r.replace("/", "px "))
        else:
            print("  border-radius: %spx;" % r)
    pads = [n.get(k) for k in ("paddingTop", "paddingRight", "paddingBottom", "paddingLeft")]
    if any(p for p in pads):
        print("  padding: %s;" % " ".join("%dpx" % round(p or 0) for p in pads))
    if n.get("itemSpacing"):
        print("  gap: %dpx;" % round(n["itemSpacing"]))
    for st in n.get("strokes", []) or []:
        if st.get("type") == "SOLID":
            print("  border: %dpx solid %s;" % (round(n.get("strokeWeight", 1)), rgba(st["color"], st.get("opacity"))))
            break
    ef = effects(n)
    if ef:
        print("  /* effects: %s */" % ef)
    if n.get("style"):
        s = n["style"]
        print("  font-family: '%s';" % s.get("fontFamily"))
        print("  font-weight: %s;" % s.get("fontWeight"))
        print("  font-size: %dpx;" % round(s.get("fontSize", 0)))
        if s.get("lineHeightPx"):
            print("  line-height: %dpx;" % round(s["lineHeightPx"]))
        if s.get("letterSpacing"):
            print("  letter-spacing: %.2fpx;" % s["letterSpacing"])
        print("  color: %s;" % fill_str(n))
    print("}")


def main():
    if len(sys.argv) < 2:
        print(__doc__)
        return
    roots = load()
    by_id = index(roots)
    cmd = sys.argv[1]
    args = sys.argv[2:]
    if cmd == "find":
        cmd_find(by_id, args[0])
    elif cmd == "node":
        cmd_node(by_id, args[0])
    elif cmd == "tree":
        cmd_tree(by_id, args[0], int(args[1]) if len(args) > 1 else 4)
    elif cmd == "text":
        cmd_text(by_id, args[0])
    elif cmd == "css":
        cmd_css(by_id, args[0])
    else:
        print(__doc__)


if __name__ == "__main__":
    main()
