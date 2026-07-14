#!/usr/bin/env bash
# PostToolUse(Bash): after a `git push`, nudge to check whether the PR
# description still matches the latest commits. This is routinely asked for
# after a push, so surface it automatically instead of waiting to be asked.
# Fires only on a real push (not --help/--dry-run).
set -uo pipefail

input=$(cat)
cmd=$(printf '%s' "$input" | jq -r '.tool_input.command // ""' 2>/dev/null)
[ -z "$cmd" ] && exit 0

printf '%s' "$cmd" | grep -qE '\bgit +push\b' || exit 0
printf '%s' "$cmd" | grep -qE '\-\-help|\-\-dry-run' && exit 0

jq -cn '{hookSpecificOutput:{hookEventName:"PostToolUse",additionalContext:"A git push just ran. If this branch has an open PR, check whether its description still reflects the latest commits (scope, new files, behavior changes) and update it with gh pr edit --body-file if stale."}}'
exit 0
