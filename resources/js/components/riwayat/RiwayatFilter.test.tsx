import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import RiwayatFilter, { type MoodOption } from './RiwayatFilter';

type Range = '8w' | '12w';

const RANGE_OPTIONS = [
    { value: '8w' as const, label: '8 minggu' },
    { value: '12w' as const, label: '12 minggu', hint: 'default' },
];

const MOOD_OPTIONS: ReadonlyArray<MoodOption> = [
    { mood: 'nyala', label: 'Nyala', hint: 'pr', swatchClass: 'bg-mood-nyala' },
    { mood: 'lemes', label: 'Lemes', hint: 'strain', swatchClass: 'bg-mood-lemes' },
];

function openPanel() {
    fireEvent.click(screen.getByRole('button', { name: /buka filter/i }));
}

describe('RiwayatFilter', () => {
    it('renders range options as links to hrefFor() once opened', () => {
        render(
            <RiwayatFilter<Range>
                range={{ value: '12w', options: RANGE_OPTIONS, hrefFor: (v) => `/aktivitas?range=${v}`, only: ['runs'] }}
            />,
        );
        openPanel();

        const link = screen.getByRole('link', { name: /8 minggu/i });
        expect(link).toHaveAttribute('href', '/aktivitas?range=8w');
        // The active option is marked, not duplicated as a link target.
        expect(screen.getByRole('link', { name: /12 minggu/i })).toHaveAttribute('aria-current', 'true');
    });

    it('toggles a mood and fires onReset', () => {
        const onToggle = vi.fn();
        const onReset = vi.fn();
        render(
            <RiwayatFilter
                mood={{ selected: new Set(['nyala']), options: MOOD_OPTIONS, onToggle }}
                onReset={onReset}
            />,
        );
        openPanel();

        fireEvent.click(screen.getByRole('button', { name: /lemes/i }));
        expect(onToggle).toHaveBeenCalledWith('lemes');

        fireEvent.click(screen.getByRole('button', { name: /reset/i }));
        expect(onReset).toHaveBeenCalled();
    });

    it('marks the selected mood as pressed, not menu-checked', () => {
        render(
            <RiwayatFilter mood={{ selected: new Set(['nyala']), options: MOOD_OPTIONS, onToggle: vi.fn() }} />,
        );
        openPanel();

        expect(screen.getByRole('button', { name: /nyala/i })).toHaveAttribute('aria-pressed', 'true');
        expect(screen.getByRole('button', { name: /lemes/i })).toHaveAttribute('aria-pressed', 'false');
    });

    it('does not adopt ARIA menu semantics (disclosure popover, not a menu)', () => {
        render(
            <RiwayatFilter<Range>
                range={{ value: '12w', options: RANGE_OPTIONS, hrefFor: (v) => `/aktivitas?range=${v}` }}
                mood={{ selected: new Set(), options: MOOD_OPTIONS, onToggle: vi.fn() }}
            />,
        );
        openPanel();

        expect(screen.queryByRole('menu')).not.toBeInTheDocument();
        expect(screen.queryByRole('menuitemradio')).not.toBeInTheDocument();
        expect(screen.queryByRole('menuitemcheckbox')).not.toBeInTheDocument();
    });

    it('returns focus to the trigger button when Escape is pressed', () => {
        render(
            <RiwayatFilter<Range>
                range={{ value: '12w', options: RANGE_OPTIONS, hrefFor: (v) => `/aktivitas?range=${v}` }}
            />,
        );
        const trigger = screen.getByRole('button', { name: /buka filter/i });
        openPanel();
        fireEvent.keyDown(document, { key: 'Escape' });
        expect(document.activeElement).toBe(trigger);
    });
});
