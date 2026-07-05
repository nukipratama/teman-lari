import { render } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { usePoll } from '@inertiajs/react';
import EmptyRunsState from './EmptyRunsState';
import { setMockPage } from '@/test/setup';

describe('EmptyRunsState', () => {
    it('starts polling recentRuns + stravaSync while a sync is in flight', () => {
        const start = vi.fn();
        const stop = vi.fn();
        vi.mocked(usePoll).mockReturnValue({ start, stop });
        setMockPage({
            auth: { user: null },
            flash: {},
            demoLoginEnabled: false,
            stravaSync: { state: 'syncing', last_synced_at: null },
        });

        render(<EmptyRunsState />);

        expect(usePoll).toHaveBeenCalledWith(
            7000,
            { only: ['recentRuns', 'stravaSync'] },
            { autoStart: false },
        );
        expect(start).toHaveBeenCalled();
        expect(stop).not.toHaveBeenCalled();
    });

    it('does not start polling when disconnected', () => {
        const start = vi.fn();
        const stop = vi.fn();
        vi.mocked(usePoll).mockReturnValue({ start, stop });
        setMockPage({
            auth: { user: null },
            flash: {},
            demoLoginEnabled: false,
            stravaSync: { state: 'disconnected', last_synced_at: null },
        });

        render(<EmptyRunsState />);

        expect(start).not.toHaveBeenCalled();
        expect(stop).toHaveBeenCalled();
    });

    it('stops polling once the sync reaches ready', () => {
        const start = vi.fn();
        const stop = vi.fn();
        vi.mocked(usePoll).mockReturnValue({ start, stop });
        setMockPage({
            auth: { user: null },
            flash: {},
            demoLoginEnabled: false,
            stravaSync: { state: 'ready', last_synced_at: '2026-07-04T00:00:00Z' },
        });

        render(<EmptyRunsState />);

        expect(start).not.toHaveBeenCalled();
        expect(stop).toHaveBeenCalled();
    });
});
