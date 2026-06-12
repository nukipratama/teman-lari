#!/bin/sh
# Restore the dev env after a browser-review run. Run inside the Sail `app`
# container:  ./vendor/bin/sail exec app sh .claude/skills/browser-review/scripts/teardown.sh
set -e

echo "→ removing screenshots…"
rm -rf storage/app/browser-review

echo "→ restoring node_modules (undo the --no-save playwright install)…"
npm ci --no-audit --no-fund

echo "✓ teardown done (native chromium stays until the container is recreated)"
