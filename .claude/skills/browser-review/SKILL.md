---
name: browser-review
description: Drive a real browser to screenshot every user-facing page across a mobile/tablet/desktop/wide viewport matrix, capture console errors, and audit for horizontal overflow — an end-to-end visual UI review. Use when asked to "browser review", "screenshot every page", "mobile UI review", "check the UI on mobile/tablet", "full browser check", or "review the app end to end" in this repo.
---

# browser-review

End-to-end visual review: log in as the demo user, **discover every page from the route table**,
screenshot each at four viewports, collect JS/console errors, and flag any horizontal overflow.
Then read the PNGs back to spot layout bugs. Everything runs **inside the Sail `app` container**
(no host browser needed), so the page list is never hardcoded — it comes from
`php artisan route:list` each run and auto-includes new pages.

## Viewport matrix (default)

Both sides of the Tailwind `lg` (1024px) breakpoint are covered on purpose — that's where the app
swaps its whole nav chrome (desktop `TopNav` ↔ `MobileTopBar` + `MobileBottomNav`):

| key | size | nav shown |
|-----|------|-----------|
| `mobile`  | 390×844  (iPhone 13)   | mobile (top bar + bottom nav) |
| `tablet`  | 834×1112 (iPad portrait) | **still mobile** (834 < 1024) |
| `desktop` | 1280×800               | desktop `TopNav` |
| `wide`    | 1536×864 (`2xl`)       | desktop, widest `max-w-page-2xl` layout |

Narrow the sweep with `VIEWPORTS=mobile` (or `mobile,wide`, etc.).

## Prerequisites

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan demo:seed          # demo user + ~126 runs, deterministic
# .env must have DEMO_LOGIN_ENABLED=true (the scripts log in via the /login demo button)
```

The app is reachable **inside the container at `http://localhost`** (host-forwarded port is
`APP_PORT=7001`, but the scripts run in the container, so use `localhost`).

## The Alpine/Playwright gotcha (do not rediscover this)

The `app` container is **Alpine Linux (musl), ARM64**. Playwright's bundled Chromium is a glibc
build and fails to launch with a misleading `spawn ... ENOENT`. Fix: use Alpine's **native** musl
Chromium and point Playwright at it. `setup.sh` does this:

- `apk add --no-cache chromium nss freetype harfbuzz ttf-freefont` (needs **root**) → `/usr/bin/chromium`
- `npm i playwright --no-save` for the JS driver only (the `--no-save` install prunes a few
  extraneous packages from `node_modules`; restore with `npm ci` in teardown)
- launch with `executablePath: '/usr/bin/chromium'` + `--no-sandbox --disable-dev-shm-usage`

Both are **ephemeral** (gone when the container is recreated) — this skill never commits browser
binaries or edits `package.json`.

## Run it

```bash
# 1. one-time setup per container lifetime (apk needs root)
docker compose exec -u root app sh .claude/skills/browser-review/scripts/setup.sh

# 2. screenshots across the viewport matrix (default all four)
./vendor/bin/sail exec app node .claude/skills/browser-review/scripts/shoot.mjs
#    e.g. just phone:    VIEWPORTS=mobile ./vendor/bin/sail exec -e VIEWPORTS=mobile app node .../shoot.mjs

# 3. horizontal-overflow audit across the matrix
./vendor/bin/sail exec app node .claude/skills/browser-review/scripts/audit.mjs

# 4. teardown (restore node_modules, remove PNGs)
./vendor/bin/sail exec app sh .claude/skills/browser-review/scripts/teardown.sh
```

Output lands in `storage/app/browser-review/<viewport>/NN-<page>-{viewport,full}.png` (gitignored
`storage/`). The script also prints any console/`pageerror` per page, and the audit prints
`HORIZ-OVERFLOW=true/false` per page per viewport (ignoring intentional `overflow-x-auto` scroll
containers and decorative `pointer-events-none` glow blobs).

## Inspect in parallel (keep the main context lean)

A sweep produces a lot of images (pages × viewports) — **don't read them all into the orchestrating
context.** Fan out one subagent per viewport (Agent tool), each reading only its own
`storage/app/browser-review/<viewport>/*-full.png` files and returning a compact findings list; send
the Agent calls in one message so they run concurrently. Merge the lists, then open only the PNGs an
agent flagged — the heavy image reads stay in the subagents.

Tell each agent its viewport's size and which nav it should see, to report only real layout bugs
(not pages that look fine), and to ignore the fixed bottom-nav appearing mid-page (a
`position: fixed` full-page-screenshot artifact). Judge each finding against the app's actual design
intent before acting — e.g. content is deliberately width-capped (`PageContainer`), so "doesn't fill
the wide screen" is usually by design, and sparse demo data can make a responsive grid look empty.

## What the scripts handle for you

- **Page discovery:** `lib.mjs` runs `php artisan route:list --json --except-vendor` and keeps the
  GET `web` pages — dropping apis, oauth handshakes, webhooks, assets, and legacy 301 redirects.
  Add a page and it's covered automatically; nothing to maintain by hand.
- **Auth:** clicks the demo button on `/login` (no Strava needed) — fresh per viewport context.
- **`{param}` pages:** resolved at runtime by scraping the first matching link off the list page
  (e.g. `/aktivitas/{activity}` → `/aktivitas/126`). If a detail page can't be sampled, the data is
  thin — **re-run `./vendor/bin/sail artisan demo:seed`** and try again.
- **Redirect dedupe:** pages reached via a 301 alias are screenshotted once (keyed by the landed URL).
- **Card-reveal modal:** the demo user can have a `pending_reveal_card_id` that pops a `Kartu baru`
  dialog over every page; the script dismisses it once after login so the pages underneath are
  reviewable. (To inspect the reveal itself, set the user's `pending_reveal_card_id` and run a
  one-off with Playwright's `reducedMotion: 'reduce'` to jump straight to its opened state.)

## Notes

- Defaults to the **local** app. Driving production (`teman-lari.caffeinecommit.my.id`) needs real
  Strava auth — out of scope here.
- This sweeps **pages**. Interactive states (the avatar logout menu, the card-reveal CTAs, equipping
  an accessory) aren't auto-driven — spot-check those with a short one-off Playwright script that
  clicks the element, screenshots, and asserts its `boundingBox()` is within the viewport.
- Scripts: `lib.mjs` (shared: viewports, login, route discovery), `shoot.mjs` (screenshots),
  `audit.mjs` (overflow), `setup.sh` / `teardown.sh`.
