import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import RunHeatmap, { type HeatmapCell } from './RunHeatmap';

describe('RunHeatmap', () => {
    it('returns null when no cells are passed', () => {
        const { container } = render(<RunHeatmap cells={[]} />);
        expect(container.firstChild).toBeNull();
    });

    it('renders a link cell for days with a single resolvable activity', () => {
        const cells: HeatmapCell[] = [
            { date: '2026-05-04', trimp: 45, distance_km: 6.3, activity_id: 12 },
            { date: '2026-05-05', trimp: null, distance_km: null, activity_id: null },
        ];
        render(<RunHeatmap cells={cells} />);
        const link = screen.getByRole('link');
        expect(link).toHaveAttribute('href', '/aktivitas/12');
        expect(link).toHaveAttribute('aria-label', expect.stringContaining('6.30 km'));
        expect(link).toHaveAttribute('aria-label', expect.stringContaining('TRIMP 45'));
    });

    it('renders a plain div for days with no activity', () => {
        const cells: HeatmapCell[] = [
            { date: '2026-05-04', trimp: null, distance_km: null, activity_id: null },
        ];
        const { container } = render(<RunHeatmap cells={cells} />);
        expect(container.querySelector('a')).toBeNull();
        // Legend buckets (5) + 1 day-cell + the wrapper, so at least the day-cell div exists.
        expect(container.querySelectorAll('div').length).toBeGreaterThan(0);
    });
});
