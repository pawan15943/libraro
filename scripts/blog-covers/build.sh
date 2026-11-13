#!/bin/sh
# Regenerates blog covers from database/seeders/blog-content/*.php (cover + topic of each post).
#   sh scripts/blog-covers/build.sh [php-binary]
# Needs macOS (swift, CoreText) and Python with Pillow for WebP (a venv path can be set in PYTHON).
set -e
ROOT=$(cd "$(dirname "$0")/../.." && pwd)
PHP=${1:-${PHP:-php}}
PYTHON=${PYTHON:-python3}
TMP=$(mktemp -d)
OUT="$ROOT/public/img/blog"
mkdir -p "$OUT"

"$PHP" -r '
$root = $argv[1]; $tmp = $argv[2];
$labels = ["software" => ["Library Software", "लाइब्रेरी सॉफ्टवेयर"], "seats" => ["Seats & Attendance", "सीट और अटेंडेंस"],
           "fees" => ["Fees & Finance", "फीस और हिसाब"], "operations" => ["Library Operations", "लाइब्रेरी संचालन"],
           "business" => ["Start a Library Business", "लाइब्रेरी बिज़नेस"]];
$specs = [];
foreach (glob("$root/database/seeders/blog-content/*.php") as $f) {
    $p = require $f; $c = $p["cover"];
    foreach (["en" => 0, "hi" => 1] as $lang => $i) {
        if (empty($c[$lang])) continue;
        $specs[] = ["out" => "$tmp/" . $p["slug"] . ($lang === "hi" ? "-hi" : "") . ".png", "title" => $c[$lang],
            "label" => $labels[$p["topic"]][$i], "lang" => $lang, "motif" => $c["motif"] ?? "dashboard",
            "logo" => "$root/public/img/libraro-white.webp", "fontEn" => "$root/public/fonts/Outfit-Bold.ttf",
            "footer" => $lang === "hi" ? "libraro.in · स्टडी लाइब्रेरी मालिकों के लिए गाइड" : "libraro.in · Guides for study library owners"];
    }
}
file_put_contents("$tmp/specs.json", json_encode($specs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
' "$ROOT" "$TMP"

swift "$ROOT/scripts/blog-covers/render.swift" "$TMP/specs.json"

"$PYTHON" - "$TMP" "$OUT" <<'PY'
import sys, glob, os
from PIL import Image
tmp, out = sys.argv[1], sys.argv[2]
for png in sorted(glob.glob(os.path.join(tmp, "*.png"))):
    name = os.path.splitext(os.path.basename(png))[0]
    Image.open(png).convert("RGB").save(os.path.join(out, name + ".webp"), "WEBP", quality=84, method=6)
    print("webp", name, os.path.getsize(os.path.join(out, name + ".webp")) // 1024, "KB")
PY
rm -rf "$TMP"
