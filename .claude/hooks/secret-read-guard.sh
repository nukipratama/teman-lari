#!/usr/bin/env bash
# PreToolUse(Read|Bash) guard: hard-deny reading raw secret files so their
# values never land in the transcript/context. Complements gitleaks (blocks
# secrets entering git) and the env-dump deny in prod-git-guard.sh. Template
# files (.env.example / .sample / .dist) are allowed - they carry no secrets.
# To inspect config, use `sail artisan config:show <key>`, not the raw file.
set -uo pipefail

input=$(cat)
tool=$(printf '%s' "$input" | jq -r '.tool_name // ""' 2>/dev/null)

decide() { # $1=deny|ask  $2=reason
  jq -cn --arg d "$1" --arg r "$2" \
    '{hookSpecificOutput:{hookEventName:"PreToolUse",permissionDecision:$d,permissionDecisionReason:$r}}'
  exit 0
}
deny() { decide deny "$1"; }

is_secret() { # $1=path
  case "$1" in
    *.example|*.sample|*.dist) return 1 ;;
  esac
  printf '%s' "$1" | grep -qiE '(^|/)\.env($|\.)|\.(pem|key|p12|pfx|keystore|jks)$|(^|/)id_(rsa|dsa|ecdsa|ed25519)($|\.)|(^|/)(credentials|auth|service-account)[^/]*\.json$'
}

REASON="Refusing to read a raw secret file - its values would leak into the session context. For a secret value, ask the user; env key NAMES are listed in .env.example."
CFG_REASON="config:show/config:get resolves env values and can surface a real secret. Every config read needs the user's explicit approval - no key is auto-classified as safe."

if [ "$tool" = "Read" ]; then
  path=$(printf '%s' "$input" | jq -r '.tool_input.file_path // ""' 2>/dev/null)
  [ -n "$path" ] && is_secret "$path" && deny "$REASON"
  exit 0
fi

if [ "$tool" = "Bash" ]; then
  cmd=$(printf '%s' "$input" | jq -r '.tool_input.command // ""' 2>/dev/null)
  scan=$(printf '%s' "$cmd" | sed "s/'[^']*'//g; s/\"[^\"]*\"//g")
  # A read-command touching a secret path (and not an obvious template file).
  if printf '%s' "$scan" | grep -qiE '\b(cat|less|more|head|tail|bat|xxd|od|strings|nl|tac|view|readlink)\b' \
     && printf '%s' "$scan" | grep -qiE '(^|[ =/])\.env($|[ .])|\.(pem|key|p12|pfx|keystore|jks)\b|\bid_(rsa|dsa|ecdsa|ed25519)\b|\b(credentials|auth|service-account)[^ ]*\.json\b' \
     && ! printf '%s' "$scan" | grep -qiE '\.(example|sample|dist)\b'; then
    deny "$REASON"
  fi
  # Any config:show/config:get can resolve a real secret value - never
  # auto-classify a "safe" key; require the user's approval on every config read.
  if printf '%s' "$scan" | grep -qiE 'config:(show|get)\b'; then
    decide ask "$CFG_REASON"
  fi
  exit 0
fi

exit 0
