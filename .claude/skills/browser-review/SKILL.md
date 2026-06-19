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
- `npm i playwright --no-save` for the JS driver only, run as the **app user** (not root, or the
  unprivileged teardown can't remove it); `teardown.sh` deletes the playwright dirs to restore the
  lockfile state
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

# 4. teardown (restore node_modules; screenshots are kept as history)
./vendor/bin/sail exec app sh .claude/skills/browser-review/scripts/teardown.sh
```

Each run lands in its own batch dir, keyed by date + execution time:
`storage/app/browser-review/<YYYY-MM-DD>/<HHMMSS>/<viewport>/NN-<page>-{viewport,full}.png`. `shoot.mjs`
clears prior batches at the start, so only the latest sweep is on disk, and prints the resolved dir as
`BATCH_DIR=...` on its last line — **capture that and pass it to the inspect workflow.** The script also prints any console/`pageerror` per page, and the audit prints
`HORIZ-OVERFLOW=true/false` per page per viewport (ignoring intentional `overflow-x-auto` scroll
containers and decorative `pointer-events-none` glow blobs).

> These PNGs are gitignored (`storage/app/.gitignore` ignores `*`) and your IDE may hide gitignored
> files — they're on disk under `storage/app/browser-review/`, not in a temp dir.

## Inspect in parallel (Sonnet subagents, keep the main context lean)

A sweep produces a lot of images — **don't read them all into the orchestrating context.** Run the
inspection as a `Workflow`: one Sonnet subagent per viewport (parallel), each reading only its own
`<BATCH_DIR>/<viewport>/*-full.png` files and returning structured findings. Pass the batch dir and the
viewports you shot as `args`, e.g. `{ "dir": "storage/app/browser-review/2026-06-19/143022", "viewports": ["mobile","wide"] }`
(`dir` is the `BATCH_DIR=` line `shoot.mjs` printed; omit `viewports` for all four). Merge the lists,
then open only the flagged PNGs to confirm before acting.

```js
export const meta = {
  name: 'browser-review-inspect',
  description: 'Read browser-review screenshots per viewport in parallel (Sonnet) and report layout bugs',
  phases: [{ title: 'Inspect', detail: 'one Sonnet agent per viewport reads its PNGs', model: 'sonnet' }],
}

const NAV = {
  mobile:  { size: '390x844',  nav: 'mobile nav (top bar + bottom nav)' },
  tablet:  { size: '834x1112', nav: 'still mobile nav (834 < 1024 lg breakpoint)' },
  desktop: { size: '1280x800', nav: 'desktop TopNav' },
  wide:    { size: '1536x864', nav: 'desktop TopNav, widest max-w-page-2xl layout' },
}
const dir = args?.dir ?? 'storage/app/browser-review'
const viewports = args?.viewports?.length ? args.viewports : Object.keys(NAV)

const FINDINGS = {
  type: 'object',
  additionalProperties: false,
  required: ['viewport', 'findings'],
  properties: {
    viewport: { type: 'string' },
    findings: {
      type: 'array',
      items: {
        type: 'object',
        additionalProperties: false,
        required: ['page', 'severity', 'issue'],
        properties: {
          page: { type: 'string' },
          severity: { type: 'string', enum: ['high', 'medium', 'low'] },
          issue: { type: 'string' },
        },
      },
    },
  },
}

phase('Inspect')
return (await parallel(viewports.map((vp) => () =>
  agent(
    `Review the "${vp}" viewport (${NAV[vp]?.size}, ${NAV[vp]?.nav}) of the teman-lari app. Read every ` +
    `*-full.png in ${dir}/${vp}/ and report ONLY real layout bugs (horizontal overflow, ` +
    `overlapping/clipped/truncated text, wrong nav chrome for this viewport, off-screen elements). Ignore by ` +
    `design: width-capped content (PageContainer / max-w-page-2xl), the fixed bottom-nav mid-page artifact, ` +
    `sparse demo-data grids, and intentional overflow-x-auto. Return only flagged pages.`,
    { label: `inspect:${vp}`, phase: 'Inspect', model: 'sonnet', effort: 'high', schema: FINDINGS }
  )
))).filter(Boolean)
```

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
