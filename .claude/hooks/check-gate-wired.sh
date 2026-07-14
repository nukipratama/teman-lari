#!/usr/bin/env bash
# SessionStart guard: verify the local git quality gate is actually wired.
# core.hooksPath got orphaned once by a folder rename (absolute path to the old
# name), silently disabling pre-commit for weeks. Warn at session start if the
# configured hooks path doesn't resolve to a dir holding the expected hooks.
# Silent when healthy.
set -uo pipefail

root=$(git rev-parse --show-toplevel 2>/dev/null) || exit 0
configured=$(git -C "$root" config --get core.hooksPath || true)

warn() {
  jq -cn --arg c "$1" \
    '{hookSpecificOutput:{hookEventName:"SessionStart",additionalContext:$c}}'
  exit 0
}

if [ -z "$configured" ]; then
  warn "WARNING: git core.hooksPath is unset - the local pre-commit gate (.githooks) is not running. Fix: git config core.hooksPath .githooks"
fi

# Resolve relative to repo root when not absolute.
case "$configured" in
  /*) dir="$configured" ;;
  *)  dir="$root/$configured" ;;
esac

if [ ! -e "$dir/pre-commit" ]; then
  warn "WARNING: git core.hooksPath ($configured) does not resolve to a hooks dir with pre-commit - the local quality gate is NOT running. Fix: git config core.hooksPath .githooks"
fi

exit 0
