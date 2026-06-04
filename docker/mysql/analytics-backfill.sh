#!/bin/sh
# One-time-effective backfill: copy ai_token_usages rows from the main schema
# into the analytics schema. INSERT IGNORE on the PK makes it safe to re-run and
# a no-op once the orphan main-schema table is dropped. Runs as root in the
# mysql container, AFTER the analytics schema has been migrated.
#
# NOT in init/ on purpose — it must run after migrations, not at volume init.
set -e

ANALYTICS_DB="${DB_ANALYTICS_DATABASE:-teman_lari_analytics}"
MAIN_DB="${DB_DATABASE:-${MYSQL_DATABASE:-teman_lari}}"
COLS="id, user_id, kind, prompt_tokens, completion_tokens, total_tokens, latency_ms, truncated, model, created_at"

has_main=$(mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -N -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${MAIN_DB}' AND table_name='ai_token_usages';")

if [ "${has_main}" = "1" ]; then
  mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e \
    "INSERT IGNORE INTO ${ANALYTICS_DB}.ai_token_usages (${COLS}) SELECT ${COLS} FROM ${MAIN_DB}.ai_token_usages;"
  echo "analytics: backfilled from ${MAIN_DB}.ai_token_usages"
else
  echo "analytics: no ${MAIN_DB}.ai_token_usages — backfill skipped"
fi
