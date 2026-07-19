#!/usr/bin/env bash
set -euo pipefail

# Restore a prod DB backup (/var/lib/temari-backups/*.sql.gz) produced by the
# deploy pipeline. Run on the homelab host, from the repo root.
#
#   ./scripts/restore-db.sh <backup.sql.gz>
#
# The "Rollback prod" workflow rolls CODE back only; after a destructive migration
# the DATA needs restoring with this first. Recommended order for that incident:
#   1. docker compose -f compose.prod.yaml stop scheduler horizon
#   2. ./scripts/restore-db.sh /var/lib/temari-backups/pre-deploy-<sha>.sql.gz
#      (and the matching analytics-pre-deploy-<sha>.sql.gz if analytics moved too)
#   3. run the "Rollback prod" GitHub workflow to roll code to :previous

COMPOSE="docker compose -f compose.prod.yaml"
backup="${1:-}"

if [ -z "$backup" ] || [ ! -f "$backup" ]; then
  echo "Usage: $0 <path-to-backup.sql.gz>" >&2
  echo "Available backups:" >&2
  ls -1t /var/lib/temari-backups/*.sql.gz 2>/dev/null >&2 || echo "  (none found)" >&2
  exit 1
fi

echo "Restoring $backup — this OVERWRITES the live database."
read -rp "Proceed? [y/N] " reply
case "$reply" in
  y | Y) ;;
  *) echo "Aborted."; exit 1 ;;
esac

# analytics-*.sql.gz was dumped with --databases (carries its own CREATE DATABASE
# + USE), so it self-targets its schema; the main dump restores into $DB_DATABASE
# from the container env. MYSQL_PWD keeps the password out of the process table.
case "${backup##*/}" in
  analytics-*)
    gunzip -c "$backup" | $COMPOSE exec -T mysql sh -c \
      'export MYSQL_PWD="$DB_PASSWORD"; mysql -h 127.0.0.1 -u"$DB_USERNAME"'
    ;;
  *)
    gunzip -c "$backup" | $COMPOSE exec -T mysql sh -c \
      'export MYSQL_PWD="$DB_PASSWORD"; mysql -h 127.0.0.1 -u"$DB_USERNAME" "$DB_DATABASE"'
    ;;
esac

echo "Restore complete from $backup."
