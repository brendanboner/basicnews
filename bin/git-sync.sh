#!/usr/bin/env bash
# CLI-only helper to commit content/data changes to git. Not for browser use.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
if [ ! -d .git ]; then
  echo "Not a git repository: $ROOT" >&2
  exit 1
fi
MSG="${1:-content: sync $(date -u +%Y-%m-%dT%H:%M:%SZ)}"
# Add content, media, cache, users? (users contain password hashes; include by default for backup - operator can adjust)
git add -A -- content media cache js css includes config user admin index.php article.php bin 2>/dev/null || true
if git diff --cached --quiet; then
  echo "No changes to commit."
  exit 0
fi
git commit -m "$MSG"
echo "Committed: $MSG"
?>