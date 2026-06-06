import { act, render } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import * as inertia from '@inertiajs/react';
import TemariMascot from './TemariMascot';
import { setMockPage } from '@/test/setup';

describe('TemariMascot', () => {
    it('draws the equipped accessories from shared state by default', () => {
        setMockPage({
            equippedAccessories: {
                medal: null,
                ikat_kepala: 'accessory.ikat_kepala_legendaris',
                pita: null,
                kaus: null,
                celana: null,
                sepatu: null,
                aura: null,
            },
        });
        // legendaris headband band is the rect at y=20.5 in UnlockedExtras.
        const { container } = render(<TemariMascot mood="adem" />);
        expect(container.innerHTML).toContain('y="20.5"');
    });

    it('does not draw an accessory the user has not equipped', () => {
        setMockPage({
            equippedAccessories: {
                medal: null,
                ikat_kepala: 'accessory.ikat_kepala_epik',
                pita: null,
                kaus: null,
                celana: null,
                sepatu: null,
                aura: null,
            },
        });
        const { container } = render(<TemariMascot mood="adem" />);
        // epik flag is present, legendaris band (y=20.5) is not.
        expect(container.innerHTML).not.toContain('y="20.5"');
    });

    it('renders the TemariCharacter SVG inside the motion wrapper', () => {
        const { container } = render(<TemariMascot mood="nyala" />);
        expect(container.querySelectorAll('svg').length).toBe(1);
    });

    it('forwards aria-label to the wrapper', () => {
        const { container } = render(<TemariMascot mood="enteng" aria-label="mood bouncy" />);
        expect(container.firstElementChild?.getAttribute('aria-label')).toBe('mood bouncy');
    });

    it('renders without crash under mood-aware idle', () => {
        const { container } = render(<TemariMascot mood="lemes" idle="mood" />);
        expect(container.firstElementChild).toBeTruthy();
    });

    it('renders with breath idle for the squished mood', () => {
        const { container } = render(<TemariMascot mood="oleng" idle="breath" />);
        expect(container.firstElementChild).toBeTruthy();
    });

    it('renders without idle when idle is "none"', () => {
        const { container } = render(<TemariMascot mood="nyala" idle="none" />);
        expect(container.firstElementChild).toBeTruthy();
    });

    it('respects sizeClass override', () => {
        const { container } = render(<TemariMascot mood="adem" sizeClass="h-9 w-9" />);
        expect(container.firstElementChild?.className).toContain('h-9');
    });

    it('falls through to breath when given an unknown mood with idle="mood"', () => {
        const { container } = render(
            <TemariMascot mood={'mystery' as unknown as 'nyala'} idle="mood" />,
        );
        expect(container.firstElementChild).toBeTruthy();
    });

    it('schedules + fires the idle fidget tick when timers advance', () => {
        vi.useFakeTimers();
        try {
            const { container, unmount } = render(<TemariMascot mood="nyala" idle="breath" />);
            act(() => {
                vi.advanceTimersByTime(25_000);
            });
            expect(container.firstElementChild).toBeTruthy();
            unmount();
        } finally {
            vi.useRealTimers();
        }
    });

    it('accepts ornaments prop without crashing', () => {
        const { container } = render(<TemariMascot mood="nyala" ornaments />);
        expect(container.firstElementChild).toBeTruthy();
    });

    it('falls back to empty unlocks when usePage throws (non-Inertia context)', () => {
        const spy = vi.spyOn(inertia, 'usePage').mockImplementation(() => {
            throw new Error('outside Inertia');
        });
        try {
            const { container } = render(<TemariMascot mood="nyala" />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        } finally {
            spy.mockRestore();
        }
    });

    it('honours an explicit unlockedAccessories prop override', () => {
        // Use a non-running mood so the front-facing TemariCharacter renders
        // (running moods use the profile variant which has no accessory overlays).
        const { container } = render(
            <TemariMascot
                mood="adem"
                unlockedAccessories={['accessory.ikat_kepala_legendaris']}
            />,
        );
        expect(container.innerHTML).toContain('y="20.5"');
    });

    it('skips unlock overlays when showUnlocks is false', () => {
        const { container } = render(
            <TemariMascot
                mood="adem"
                showUnlocks={false}
                unlockedAccessories={['accessory.ikat_kepala_legendaris']}
            />,
        );
        expect(container.innerHTML).not.toContain('y="20.5"');
    });
});
