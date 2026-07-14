#!/usr/bin/env bash
# PreToolUse(Bash) guard: a direct `phpstan analyse` without --debug hits the
# nette parallel-cache race in the Sail container. Deny with a nudge so the
# agent re-runs single-process. Does not touch `composer check` (phpstan runs
# inside it, not as a literal token). Defers on non-phpstan commands.
set -uo pipefail

input=$(cat)
cmd=$(printf '%s' "$input" | jq -r '.tool_input.command // ""' 2>/dev/null)
[ -z "$cmd" ] && exit 0

has() { printf '%s' "$cmd" | grep -qiE "$1"; }

if has '\bphpstan\b' && has '\banaly[sz]e\b' && ! has '(--debug|--help|-h\b)'; then
  jq -cn '{hookSpecificOutput:{hookEventName:"PreToolUse",permissionDecision:"deny",
    permissionDecisionReason:"Run phpstan with --debug locally (single-process) - the parallel run races on the nette cache in Sail and crashes. Retry: ./vendor/bin/sail bin phpstan analyse --debug"}}'
  exit 0
fi

exit 0
