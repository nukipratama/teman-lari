import { act, renderHook, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/react';
import { useAnalysisTrigger } from './useAnalysisTrigger';
import type { AnalysisPayload } from '@/types/inertia';

const fetchMock = vi.fn();

function payload(overrides: Partial<AnalysisPayload> = {}): AnalysisPayload {
    return {
        id: null,
        status: 'pending',
        content: null,
        type: 'briefing_headline',
        subject_type: 'briefing_user_day',
        subject_id: 1,
        discriminator: null,
        ...overrides,
    };
}

beforeEach(() => {
    globalThis.fetch = fetchMock as unknown as typeof fetch;
    fetchMock.mockReset();
    vi.mocked(router.reload).mockReset();
    document.head.innerHTML = '<meta name="csrf-token" content="test-token" />';
});

afterEach(() => {
    document.head.innerHTML = '';
    vi.useRealTimers();
});

describe('useAnalysisTrigger', () => {
    it('optimistically flips status to queued and updates from response', async () => {
        fetchMock.mockResolvedValue({
            ok: true,
            json: async () => payload({ status: 'queued', id: 42 }),
        });

        const { result } = renderHook(() => useAnalysisTrigger(payload(), []));
        expect(result.current.status).toBe('pending');

        await act(async () => {
            await result.current.trigger();
        });

        expect(result.current.status).toBe('queued');
        expect(result.current.error).toBeNull();
        expect(fetchMock).toHaveBeenCalledOnce();
    });

    it('encodes discriminator into query string', async () => {
        fetchMock.mockResolvedValue({ ok: true, json: async () => payload({ status: 'queued' }) });

        const { result } = renderHook(() =>
            useAnalysisTrigger(payload({ discriminator: '2026-05-19' }), []),
        );
        await act(async () => {
            await result.current.trigger();
        });
        const url = fetchMock.mock.calls[0][0] as string;
        expect(url).toContain('?discriminator=2026-05-19');
    });

    it('sets status=failed and captures error when fetch !ok', async () => {
        fetchMock.mockResolvedValue({ ok: false, status: 500, json: async () => ({}) });

        const { result } = renderHook(() => useAnalysisTrigger(payload(), []));
        await act(async () => {
            await result.current.trigger();
        });
        await waitFor(() => expect(result.current.status).toBe('failed'));
        expect(result.current.error).toBeTruthy();
    });

    it('reloads inertia props when reload list is provided', async () => {
        fetchMock.mockResolvedValue({ ok: true, json: async () => payload({ status: 'queued' }) });

        const { result } = renderHook(() => useAnalysisTrigger(payload(), ['briefing']));
        await act(async () => {
            await result.current.trigger();
        });
        expect(result.current.status).toBe('queued');
    });

    it('catches non-Error throwables and records error string', async () => {
        fetchMock.mockRejectedValue('network fail');

        const { result } = renderHook(() => useAnalysisTrigger(payload(), []));
        await act(async () => {
            await result.current.trigger();
        });
        await waitFor(() => expect(result.current.status).toBe('failed'));
        expect(result.current.error).toBe('network fail');
    });

    it('falls back to empty CSRF when meta tag is missing', async () => {
        document.head.innerHTML = '';
        fetchMock.mockResolvedValue({ ok: true, json: async () => payload({ status: 'queued' }) });

        const { result } = renderHook(() => useAnalysisTrigger(payload(), []));
        await act(async () => {
            await result.current.trigger();
        });
        const init = fetchMock.mock.calls[0][1] as RequestInit;
        expect((init.headers as Record<string, string>)['X-CSRF-TOKEN']).toBe('');
    });

    it('polls router.reload every 3s while status is queued, stops on done', async () => {
        vi.useFakeTimers();
        const { rerender } = renderHook(
            ({ p }: { p: AnalysisPayload }) => useAnalysisTrigger(p, ['briefing']),
            { initialProps: { p: payload({ status: 'queued' }) } },
        );

        await act(async () => {
            vi.advanceTimersByTime(3000);
        });
        expect(router.reload).toHaveBeenCalledTimes(1);

        await act(async () => {
            vi.advanceTimersByTime(3000);
        });
        expect(router.reload).toHaveBeenCalledTimes(2);

        rerender({ p: payload({ status: 'done', content: 'ok' }) });
        await act(async () => {
            vi.advanceTimersByTime(9000);
        });
        expect(router.reload).toHaveBeenCalledTimes(2);
    });

    it('does not poll when reload props is empty', async () => {
        vi.useFakeTimers();
        renderHook(() => useAnalysisTrigger(payload({ status: 'queued' }), []));
        await act(async () => {
            vi.advanceTimersByTime(9000);
        });
        expect(router.reload).not.toHaveBeenCalled();
    });

    it('syncs local status when payload.status prop changes', async () => {
        const { result, rerender } = renderHook(
            ({ p }: { p: AnalysisPayload }) => useAnalysisTrigger(p, []),
            { initialProps: { p: payload({ status: 'queued' }) } },
        );
        expect(result.current.status).toBe('queued');

        await act(async () => {
            rerender({ p: payload({ status: 'done', content: 'fresh' }) });
        });
        expect(result.current.status).toBe('done');
    });

    it('shares a single polling interval across multiple hook instances with the same reload set', async () => {
        vi.useFakeTimers();
        const { unmount: unmountA } = renderHook(() =>
            useAnalysisTrigger(payload({ status: 'queued', subject_id: 1 }), ['briefing']),
        );
        const { unmount: unmountB } = renderHook(() =>
            useAnalysisTrigger(payload({ status: 'queued', subject_id: 2 }), ['briefing']),
        );
        const { unmount: unmountC } = renderHook(() =>
            useAnalysisTrigger(payload({ status: 'processing', subject_id: 3 }), ['briefing']),
        );

        await act(async () => {
            vi.advanceTimersByTime(3000);
        });
        // One shared interval fires once per tick, not three.
        expect(router.reload).toHaveBeenCalledTimes(1);

        await act(async () => {
            vi.advanceTimersByTime(3000);
        });
        expect(router.reload).toHaveBeenCalledTimes(2);

        unmountA();
        unmountB();
        await act(async () => {
            vi.advanceTimersByTime(3000);
        });
        // C still subscribed → polling continues.
        expect(router.reload).toHaveBeenCalledTimes(3);

        unmountC();
        await act(async () => {
            vi.advanceTimersByTime(6000);
        });
        // All unmounted → polling stopped.
        expect(router.reload).toHaveBeenCalledTimes(3);
    });

    it('initializes retryAfterSeconds from the prop', () => {
        const { result } = renderHook(() =>
            useAnalysisTrigger(payload({ status: 'done', content: 'x', retry_after_seconds: 123 }), []),
        );
        expect(result.current.retryAfterSeconds).toBe(123);
    });

    it('updates retryAfterSeconds from the POST response without waiting for a prop sync', async () => {
        fetchMock.mockResolvedValue({
            ok: true,
            json: async () => payload({ status: 'done', content: 'x', retry_after_seconds: 270 }),
        });

        const { result } = renderHook(() =>
            useAnalysisTrigger(payload({ status: 'done', content: 'x' }), []),
        );
        expect(result.current.retryAfterSeconds).toBeNull();

        await act(async () => {
            await result.current.trigger();
        });

        expect(result.current.retryAfterSeconds).toBe(270);
    });

    it('syncs retryAfterSeconds when payload.retry_after_seconds prop changes', async () => {
        const { result, rerender } = renderHook(
            ({ p }: { p: AnalysisPayload }) => useAnalysisTrigger(p, []),
            { initialProps: { p: payload({ status: 'done', content: 'x' }) } },
        );
        expect(result.current.retryAfterSeconds).toBeNull();

        await act(async () => {
            rerender({ p: payload({ status: 'done', content: 'x', retry_after_seconds: 88 }) });
        });

        expect(result.current.retryAfterSeconds).toBe(88);
    });

    it('clears retryAfterSeconds when the cooldown expires server-side (prop → null)', async () => {
        const { result, rerender } = renderHook(
            ({ p }: { p: AnalysisPayload }) => useAnalysisTrigger(p, []),
            { initialProps: { p: payload({ status: 'done', content: 'x', retry_after_seconds: 60 }) } },
        );
        expect(result.current.retryAfterSeconds).toBe(60);

        await act(async () => {
            rerender({ p: payload({ status: 'done', content: 'x', retry_after_seconds: null }) });
        });

        expect(result.current.retryAfterSeconds).toBeNull();
    });

    it('debounces rapid trigger() calls within 2s window', async () => {
        fetchMock.mockResolvedValue({ ok: true, json: async () => payload({ status: 'queued' }) });

        const { result } = renderHook(() => useAnalysisTrigger(payload({ status: 'done', content: 'x' }), []));

        await act(async () => {
            await result.current.trigger();
        });
        expect(fetchMock).toHaveBeenCalledTimes(1);

        await act(async () => {
            await result.current.trigger();
        });
        expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    it('pauses polling when document is hidden, resumes on visibility change', async () => {
        vi.useFakeTimers();
        const visibilityDescriptor = Object.getOwnPropertyDescriptor(Document.prototype, 'hidden');
        let hidden = false;
        Object.defineProperty(document, 'hidden', { configurable: true, get: () => hidden });

        try {
            renderHook(() => useAnalysisTrigger(payload({ status: 'queued' }), ['briefing']));

            await act(async () => {
                vi.advanceTimersByTime(3000);
            });
            expect(router.reload).toHaveBeenCalledTimes(1);

            hidden = true;
            await act(async () => {
                document.dispatchEvent(new Event('visibilitychange'));
                vi.advanceTimersByTime(6000);
            });
            expect(router.reload).toHaveBeenCalledTimes(1);

            hidden = false;
            await act(async () => {
                document.dispatchEvent(new Event('visibilitychange'));
            });
            expect(router.reload).toHaveBeenCalledTimes(2);

            await act(async () => {
                vi.advanceTimersByTime(3000);
            });
            expect(router.reload).toHaveBeenCalledTimes(3);
        } finally {
            if (visibilityDescriptor) {
                Object.defineProperty(document, 'hidden', visibilityDescriptor);
            }
        }
    });

    it('invokes onUpdate callback with next payload', async () => {
        const onUpdate = vi.fn();
        const next = payload({ status: 'queued', id: 7 });
        fetchMock.mockResolvedValue({ ok: true, json: async () => next });

        const { result } = renderHook(() => useAnalysisTrigger(payload(), [], { onUpdate }));
        await act(async () => {
            await result.current.trigger();
        });
        expect(onUpdate).toHaveBeenCalledWith(next);
    });
});
