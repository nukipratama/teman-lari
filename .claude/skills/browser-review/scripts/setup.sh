#!/bin/sh
# Idempotent browser-review setup. Run inside the Sail `app` container as ROOT
# (apk needs root):  docker compose exec -u root app sh .claude/skills/browser-review/scripts/setup.sh
#
# The container is Alpine ARM64 (musl), so Playwright's bundled glibc Chromium
# can't run — install Alpine's native chromium and let shoot.mjs/audit.mjs point
# Playwright at /usr/bin/chromium. Both installs are ephemeral (lost on recreate).
set -e

if [ ! -x /usr/bin/chromium ]; then
  echo "→ installing native chromium (apk)…"
  apk add --no-cache chromium nss freetype harfbuzz ttf-freefont font-noto-emoji
else
  echo "→ chromium already present at /usr/bin/chromium"
fi

if [ ! -d node_modules/playwright ]; then
  echo "→ installing playwright js driver (--no-save)…"
  # --no-save keeps package.json/lock untouched; teardown.sh restores node_modules with `npm ci`.
  npm i playwright --no-save --no-audit --no-fund
else
  echo "→ playwright already installed"
fi

echo "✓ browser-review setup ready"
