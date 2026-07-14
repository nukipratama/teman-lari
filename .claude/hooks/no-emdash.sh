#!/usr/bin/env bash
# PreToolUse(Edit|Write) guard: em-dashes (U+2014) read as an AI/translation tell
# in Indonesian copy and leak into LLM prompts. Block them in added content to
# source/copy files; use comma, period, colon, or parens instead. Defers on
# other file types and when no em-dash is being added.
set -uo pipefail

input=$(cat)
path=$(printf '%s' "$input" | jq -r '.tool_input.file_path // ""' 2>/dev/null)
added=$(printf '%s' "$input" | jq -r '.tool_input.new_string // .tool_input.content // ""' 2>/dev/null)
[ -z "$path" ] && exit 0

# Guard only where the rule applies: UI copy (.tsx/.ts) and LLM prompts (.php).
# Not docs or memory notes (.md) - the rule is about shipped copy + prompts.
printf '%s' "$path" | grep -qE '\.(tsx?|php)$' || exit 0

EMDASH=$(printf '\xe2\x80\x94')
if printf '%s' "$added" | grep -qF "$EMDASH"; then
  jq -cn '{hookSpecificOutput:{hookEventName:"PreToolUse",permissionDecision:"deny",
    permissionDecisionReason:"No em-dashes (U+2014) - they read as an AI tell in Indonesian copy and leak into prompts. Use a comma, period, colon, or parentheses instead."}}'
  exit 0
fi

exit 0
