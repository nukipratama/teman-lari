# TODO: Pulse and Horizon dashboards render empty (CSP blocks Alpine)

**Status:** diagnosed, not fixed. Prod only.

## Symptom

`https://temari.caffeinecommit.my.id/pulse` renders the page chrome (header, card frames)
but every card body is empty. The browser console fills with:

```
Uncaught EvalError: Evaluating a string as JavaScript violates the following Content
Security Policy directive because 'unsafe-eval' is not an allowed source of script:
script-src 'self' 'unsafe-inline' https://static.cloudflareinsights.com
```

Stack frames: `new AsyncFunction` → `safeAsyncFunction` → `generateFunctionFromString`
→ `generateEvaluatorFromString` → `normalEvaluator` → `evaluateLater`.

## Cause

Pulse and Horizon are Livewire/Alpine dashboards. Alpine compiles `x-data` / `x-show`
expressions at runtime via `new Function(...)`, which CSP classifies as `eval`. The origin
sets one global policy at `docker/Caddyfile:80` and it has no `'unsafe-eval'`, so every
Alpine expression throws and no card ever populates.

Only these two routes are affected. `/ai-usage` is an Inertia/React page and needs nothing.

## Fix

Scope the relaxation to the two ops routes rather than weakening the policy app-wide. Add
before the catch-all `handle {}` block in `docker/Caddyfile`:

```caddyfile
@ops path /horizon /horizon/* /pulse /pulse/*
handle @ops {
    header -Server
    header X-Frame-Options "DENY"
    header X-Content-Type-Options "nosniff"
    header Referrer-Policy "strict-origin-when-cross-origin"
    header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://static.cloudflareinsights.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://cloudflareinsights.com; worker-src 'self'; manifest-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'"
    php_server
}
```

Both routes authorize on `is_admin` inside Laravel and sit behind Cloudflare Access, so the
blast radius of `'unsafe-eval'` there is an already-authenticated admin surface.

## Before deploying

- `caddy validate --config docker/Caddyfile --adapter caddyfile` against the real image —
  a Caddyfile that fails to parse takes the whole origin down, not just `/pulse`.
- Confirm the catch-all still applies to every other path (curl a normal page, assert the
  response header has no `unsafe-eval`).
- Land it atomically. Two past prod outages came from multi-side config changes applied
  piecemeal.
