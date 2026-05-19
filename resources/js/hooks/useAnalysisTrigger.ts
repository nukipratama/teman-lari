import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import type { AnalysisPayload, AnalysisStatus } from '@/types/inertia';

interface TriggerOptions {
    onUpdate?: (next: AnalysisPayload) => void;
}

interface TriggerResult {
    status: AnalysisStatus;
    pending: boolean;
    error: string | null;
    trigger: () => Promise<void>;
}

function csrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
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

    const trigger = useCallback(async () => {
        if (pending) return;
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
                throw new Error(`Trigger failed (${response.status})`);
            }

            const next = (await response.json()) as AnalysisPayload;
            setStatus(next.status);
            options.onUpdate?.(next);

            if (inertiaReloadProps.length > 0) {
                router.reload({ only: inertiaReloadProps });
            }
        } catch (err) {
            setStatus('failed');
            setError(err instanceof Error ? err.message : String(err));
        } finally {
            setPending(false);
        }
    }, [pending, payload.type, payload.subject_id, payload.discriminator, inertiaReloadProps, options]);

    return { status, pending, error, trigger };
}
