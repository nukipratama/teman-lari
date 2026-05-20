import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { AnalysisPayload, AnalysisStatus } from '@/types/inertia';

const POLL_INTERVAL_MS = 3000;
const POLL_MAX_ATTEMPTS = 60;
const TRIGGER_DEBOUNCE_MS = 2000;

export const RATE_LIMITED_ERROR = 'rate_limited';

interface TriggerOptions {
    onUpdate?: (next: AnalysisPayload) => void;
}

interface TriggerResult {
    status: AnalysisStatus;
    pending: boolean;
    error: string | null;
    /**
     * Server-computed cooldown countdown source of truth — synced from the
     * latest POST response (instant) AND from prop updates (after Inertia
     * partial reload). Consumers should prefer this over `payload.retry_after_seconds`
     * directly so they don't get a brief enable→disable flicker between the
     * trigger response and the reload completing.
     */
    retryAfterSeconds: number | null;
    trigger: () => Promise<void>;
}

function csrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

// Shared FloatingTemari badge counts (`aiActivity`) tag along on every
// analysis-triggered partial reload so the floating mascot stays in sync at
// the analysis poll cadence instead of its own 5s fallback.
function withAiActivity(only: string[]): string[] {
    return only.includes('aiActivity') ? only : [...only, 'aiActivity'];
}

// Module-level refcounted poll registry. Multiple in-flight analyses sharing
// the same reload set (the 4 activity insights, the 2 briefing analyses)
// share a single interval + visibility listener instead of each spinning up
// their own — and the slot tears down once the last in-flight subscriber
// unsubscribes (i.e. all analyses settle).
interface PollSlot {
    key: string;
    refs: number;
    only: string[];
    interval: ReturnType<typeof setInterval> | null;
    attempts: number;
    onVisibility: () => void;
}
const pollSlots = new Map<string, PollSlot>();

function startSlot(slot: PollSlot): void {
    if (slot.interval !== null || globalThis.document.hidden) return;
    slot.interval = globalThis.setInterval(() => {
        slot.attempts += 1;
        if (slot.attempts > POLL_MAX_ATTEMPTS) {
            retireSlot(slot);
            return;
        }
        router.reload({ only: slot.only });
    }, POLL_INTERVAL_MS);
}

function stopSlot(slot: PollSlot): void {
    if (slot.interval === null) return;
    globalThis.clearInterval(slot.interval);
    slot.interval = null;
}

// Stop the interval AND detach the visibility listener AND drop the slot from
// the registry. Used by the max-attempts give-up path so we don't leak a
// listener for a slot that no longer polls.
function retireSlot(slot: PollSlot): void {
    stopSlot(slot);
    globalThis.document.removeEventListener('visibilitychange', slot.onVisibility);
    pollSlots.delete(slot.key);
}

function subscribePoll(only: string[]): () => void {
    const key = only.join('|');
    let slot = pollSlots.get(key);
    if (slot === undefined) {
        // The onVisibility closure references `slot` lazily — it isn't called
        // until the listener fires, by which point `slot` is fully initialized.
        const created: PollSlot = {
            key,
            refs: 0,
            only,
            interval: null,
            attempts: 0,
            onVisibility: () => {
                if (globalThis.document.hidden) {
                    stopSlot(created);
                } else {
                    router.reload({ only: created.only });
                    startSlot(created);
                }
            },
        };
        slot = created;
        pollSlots.set(key, created);
        globalThis.document.addEventListener('visibilitychange', created.onVisibility);
        startSlot(created);
    }
    slot.refs += 1;
    return () => {
        slot.refs -= 1;
        if (slot.refs <= 0) {
            retireSlot(slot);
        }
    };
}

/**
 * POST `/api/analyses/{type}/{subjectId}/trigger?discriminator=...` to enqueue
 * (or re-enqueue) an analysis. Optimistically flips local status to `queued`
 * while the request is in flight; falls back to `failed` on error.
 */
export function useAnalysisTrigger(
    payload: AnalysisPayload,
    inertiaReloadProps: string[],
    options: TriggerOptions = {},
): TriggerResult {
    const [status, setStatus] = useState<AnalysisStatus>(payload.status);
    const [pending, setPending] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [retryAfterSeconds, setRetryAfterSeconds] = useState<number | null>(payload.retry_after_seconds ?? null);
    const lastTriggeredAtRef = useRef(0);

    const trigger = useCallback(async () => {
        if (pending) return;
        const now = Date.now();
        if (now - lastTriggeredAtRef.current < TRIGGER_DEBOUNCE_MS) return;
        lastTriggeredAtRef.current = now;
        setPending(true);
        setError(null);
        setStatus('queued');

        const base = `/api/analyses/${payload.type}/${payload.subject_id}/trigger`;
        const url = payload.discriminator
            ? `${base}?discriminator=${encodeURIComponent(payload.discriminator)}`
            : base;

        try {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(response.status === 429 ? RATE_LIMITED_ERROR : `Trigger failed (${response.status})`);
            }

            const next = (await response.json()) as AnalysisPayload;
            setStatus(next.status);
            setRetryAfterSeconds(next.retry_after_seconds ?? null);
            options.onUpdate?.(next);

            if (inertiaReloadProps.length > 0) {
                router.reload({ only: withAiActivity(inertiaReloadProps) });
            }
        } catch (err) {
            setStatus('failed');
            setError(err instanceof Error ? err.message : String(err));
        } finally {
            setPending(false);
        }
    }, [pending, payload.type, payload.subject_id, payload.discriminator, inertiaReloadProps, options]);

    useEffect(() => {
        setStatus(payload.status);
    }, [payload.status]);

    useEffect(() => {
        setRetryAfterSeconds(payload.retry_after_seconds ?? null);
    }, [payload.retry_after_seconds]);

    const reloadKey = inertiaReloadProps.join('|');
    const isInFlight = payload.status === 'queued' || payload.status === 'processing';
    useEffect(() => {
        if (!isInFlight || reloadKey === '') return;
        return subscribePoll(withAiActivity(reloadKey.split('|')));
    }, [isInFlight, reloadKey]);

    return { status, pending, error, retryAfterSeconds, trigger };
}
