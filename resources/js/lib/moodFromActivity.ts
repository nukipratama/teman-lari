import type { ActivityDetail, Mood } from '@/types/inertia';

// Quick heuristic mood for an activity row when the backend hasn't
// attached a mood yet (e.g. the `/runs` list). Anchored on TRIMP because
// that's the single most-correlated number with "how the run felt."
//
// Thresholds are eyeballed for a recreational runner — tune via real
// data once we have it. Returns a `Mood` so the mascot has variety in
// the list view instead of every row defaulting to `dim`.
export function moodFromActivity(detail: ActivityDetail): Mood {
    const trimp = detail.trimp_edwards ?? 0;
    const km = (detail.distance ?? 0) / 1000;

    if (trimp >= 200) return 'wobble'; // crushing effort
    if (trimp >= 140) return 'bouncy'; // solid hard session
    if (trimp >= 90 && km >= 12) return 'squished'; // long run drained
    if (trimp >= 60) return 'glow'; // good easy / aerobic
    if (trimp >= 30) return 'spinning'; // short / interval-ish
    return 'dim'; // very light / shake-out
}
