#!/usr/bin/env bash
# Basic deployment test suite (CLI). Safe to run on deployed systems.
set -u
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$DIR/.." && pwd)"
BASE_URL=""
for a in "$@"; do
  case "$a" in --url=*) BASE_URL="${a#--url=}";; esac
done

PASS=0; FAIL=0
ok() { echo "PASS: $*"; PASS=$((PASS+1)); }
bad() { echo "FAIL: $*"; FAIL=$((FAIL+1)); }
check() { if eval "$2"; then ok "$1"; else bad "$1"; fi; }

echo "== BasicWeb deployment tests =="
echo "Root: $ROOT"

check "php CLI available" "command -v php >/dev/null 2>&1"
if command -v php >/dev/null 2>&1; then
  PV="$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo unknown)"
  echo "INFO: PHP $PV"
  check "php >= 7.2 (basic syntax/features)" "php -r 'exit(version_compare(PHP_VERSION,\"7.2.0\",\">=\")?0:1);'"
  check "php json extension" "php -r 'exit(function_exists(\"json_encode\")?0:1);'"
  check "php mbstring extension" "php -r 'exit(function_exists(\"mb_strlen\")?0:1);'"
  check "php session support" "php -r 'exit(function_exists(\"session_start\")?0:1);'"
fi

for f in config/config.php includes/functions.php index.php article.php user/login.php user/edit.php admin/login.php bin/rebuild.php; do
  check "file exists: $f" "[ -f \"$ROOT/$f\" ]"
done
for d in content content/_trash cache media users user admin css includes js bin; do
  check "dir exists: $d" "[ -d \"$ROOT/$d\" ]"
done
for d in content content/_trash cache media; do
  check "writable: $d" "[ -w \"$ROOT/$d\" ]"
done

check "admin user file present" "[ -f \"$ROOT/users/admin.json\" ]"
check "PHP lint config" "php -l \"$ROOT/config/config.php\" >/dev/null 2>&1"
check "PHP lint functions" "php -l \"$ROOT/includes/functions.php\" >/dev/null 2>&1"
check "PHP lint index" "php -l \"$ROOT/index.php\" >/dev/null 2>&1"

# Functional checks
FUNC="$(php -r '$r=$argv[1]; require $r."/config/config.php"; require $r."/includes/functions.php"; $h=simpleMarkdown("# T\n\nHi"); if(strpos($h,"<h1>")===false) exit(1); $m=loadManifest(); if(!is_array($m)) exit(1); echo "ok";' "$ROOT" 2>/dev/null || true)"
check "core functions load (markdown/manifest)" "[ \"$FUNC\" = \"ok\" ]"

if [ -n "$BASE_URL" ]; then
  if command -v curl >/dev/null 2>&1; then
    code="$(curl -sS -o /dev/null -w '%{http_code}' "$BASE_URL/" || echo 000)"
    check "HTTP GET $BASE_URL/ (200/302)" "[ \"$code\" = 200 ] || [ \"$code\" = 302 ]"
    code="$(curl -sS -o /dev/null -w '%{http_code}' "$BASE_URL/user/login.php" || echo 000)"
    check "HTTP GET user/login.php (200)" "[ \"$code\" = 200 ]"
  else
    echo "INFO: curl not found; skipping HTTP checks"
  fi
fi

echo "== Results: $PASS passed, $FAIL failed =="
[ "$FAIL" -eq 0 ]
