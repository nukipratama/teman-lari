# Teman Lari

Personal Laravel app, vibe-coded end-to-end. Fully containerized via Laravel Sail — no PHP/Composer/Node on the host.

## Stack

- Laravel 13.5.0 + Blade + Tailwind (via `@tailwindcss/vite`)
- PHP 8.4 (pinned in compose.yaml)
- MySQL 8.4 (dev + isolated test container)
- Redis (dev + isolated test container)
- Mailpit (mail catcher, UI at port 7006)
- Pest 4 + Larastan level 8 + Pint (PSR-12) + Rector + Infection
- Telescope (local debug), Horizon (queue dashboard), Pulse (perf dashboard)

## First-time setup

```bash
# 1. Wire up the project's git hooks dir (one-off per clone)
bin/setup-hooks.sh

# 2. Bring up the Sail stack (first run pulls images, ~2-5 min)
./vendor/bin/sail up -d

# 3. Set Sail file ownership env vars (adjust to your host UID/GID)
echo "WWWUSER=$(id -u)" >> .env
echo "WWWGROUP=$(id -g)" >> .env

# 4. App setup
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# 5. (Optional) Install Boost agent guidance (interactive)
./vendor/bin/sail artisan boost:install
```

App is at **http://localhost:7001**.

## Day-to-day

```bash
./vendor/bin/sail composer run dev   # Vite HMR + queue listener + log watcher
./vendor/bin/sail pest               # tests
./vendor/bin/sail pint               # auto-format
./vendor/bin/sail phpstan analyse    # static analysis
./vendor/bin/sail rector --dry-run   # refactor suggestions
```

## Ports (host → container)

| Service        | Host port | Internal |
|---------------:|:---------:|:--------:|
| App (Nginx)    | 7001      | 80       |
| Vite HMR       | 7002      | 5173     |
| MySQL (dev)    | 7003      | 3306     |
| Redis (dev)    | 7004      | 6379     |
| Mailpit SMTP   | 7005      | 1025     |
| Mailpit UI     | 7006      | 8025     |

The test stack (`mysql_test`, `redis_test`) runs without host port forwards — tests reach it over the compose network.

## Test stack

Pest tests run against `mysql_test` (tmpfs-backed, ephemeral) and `redis_test` containers, configured in `phpunit.xml`. Same image versions as prod for parity. Each `sail up` gives a fresh database; `RefreshDatabase` trait handles per-test reset.

CI uses GitHub Actions service containers (mysql:8.4 + redis:alpine) — every workflow run gets a fresh DB.

## Quality gates

| Where        | Runs                                                                  |
|:-------------|:----------------------------------------------------------------------|
| pre-commit   | `pint` (auto-format staged PHP) + `phpstan` (whole `app/`)            |
| commit-msg   | Conventional Commits format check                                     |
| prepare-commit-msg | Auto-append entry to `CHANGELOG.md`                             |
| CI           | `pint --test`, `phpstan`, `rector --dry-run`, `pest --coverage`, `infection` |

100% line coverage gate (`pest --min=100`) is wired in CI from commit #2 onwards (commit #1 ships skeleton classes that aren't fully covered yet).

## Deploy

Target: **Laravel Cloud** via GitHub auto-deploy. Push to `main` → Cloud builds + ships.

Production env overrides are documented in `.env.example` (commented out): `LOG_CHANNEL=stderr`, `APP_ENV=production`, etc. Set those in the Cloud dashboard, do not commit values.

The bundled `laravel/cloud-cli` is for terminal-side ops (logs, env, ad-hoc artisan against prod):

```bash
./vendor/bin/sail exec laravel.test ./vendor/bin/cloud login
./vendor/bin/sail exec laravel.test ./vendor/bin/cloud logs --tail
```
