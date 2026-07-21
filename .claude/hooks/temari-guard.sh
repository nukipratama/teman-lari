#!/usr/bin/env bash
# PreToolUse(Bash). temari-only rules; everything else lives in ~/.claude/hooks/guard.sh.
set -uo pipefail

input=$(cat)
cmd=$(printf '%s' "$input" | jq -r '.tool_input.command // ""' 2>/dev/null)
[ -z "$cmd" ] && exit 0

scan=$(printf '%s' "$cmd" | sed "s/'[^']*'//g; s/\"[^\"]*\"//g")
has() { printf '%s' "$scan" | grep -qiE "$1"; }

if has '\bphpstan\b' && has '\banaly[sz]e\b' && ! has '(--debug|--help|-h\b)'; then
  jq -cn '{hookSpecificOutput:{hookEventName:"PreToolUse",permissionDecision:"deny",
    permissionDecisionReason:"Run phpstan with --debug locally (single-process) - the parallel run races on the nette cache in Sail and crashes. Retry: ./vendor/bin/sail bin phpstan analyse --debug"}}'
  exit 0
fi

exit 0
