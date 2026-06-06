import { render, screen, fireEvent } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Kartu from './Kartu';
import type { Rarity } from '@/types/inertia';

const SAMPLE_POLYLINE = '_p~iF~ps|U_ulLnnqC_mqNvxq`@';

describe('Kartu', () => {
    it('renders name and hero km', () => {
        render(<Kartu name="Pejuang Subuh" km="8.4" durasi="42:11" trimp={68} />);
        expect(screen.getByText('Pejuang Subuh')).toBeInTheDocument();
        expect(screen.getByText('8.4')).toBeInTheDocument();
    });

    it('shows duration in the stat row on the full tier', () => {
        render(<Kartu name="x" km="8.4" durasi="42:11" trimp={68} size="lg" />);
        // duration joins the stat row only on the full tier
        expect(screen.getByText(/42:11/)).toBeInTheDocument();
    });

    it('shows the TRIMP number in the floating badge', () => {
        render(<Kartu name="x" km="1" durasi="1:00" trimp={68} />);
        // TRIMP is rendered as a number in the TRIMPBadge, not "TRIMP 68"
        expect(screen.getByText('68')).toBeInTheDocument();
    });

    it.each(['common', 'uncommon', 'rare', 'epic', 'legendary'] satisfies Rarity[])(
        'renders the rarity set symbol for %s',
        (rarity) => {
            render(<Kartu name="x" km="1" durasi="1:00" trimp={1} rarity={rarity} />);
            const symbol = { common: '●', uncommon: '◆', rare: '★', epic: '✦', legendary: '✺' }[rarity];
            expect(screen.getByText(symbol)).toBeInTheDocument();
        },
    );

    it('renders the edition mark when provided', () => {
        render(<Kartu name="x" km="1" durasi="1:00" trimp={1} edition={{ index: 3, total: 12 }} />);
        expect(
            screen.getByText((_, el) => el?.tagName === 'SPAN' && el.textContent === '#3/12'),
        ).toBeInTheDocument();
    });

    it('draws the route glyph when a polyline is present', () => {
        const { container } = render(
            <Kartu name="x" km="1" durasi="1:00" trimp={1} polyline={SAMPLE_POLYLINE} />,
        );
        expect(container.querySelector('[data-variant="route"]')).not.toBeNull();
        expect(container.querySelector('[data-variant="route"] path')).not.toBeNull();
    });

    it('renders the art zone at all sizes including compact', () => {
        // Art zone is always present (mascot background + route glyph fallback).
        const { container } = render(<Kartu name="x" km="1" durasi="1:00" trimp={1} size="md" />);
        // TemariProto always renders an SVG in the art zone.
        expect(container.querySelector('svg')).not.toBeNull();
    });

    it('shows the route glyph fallback in the art zone when there is no route data', () => {
        const { container } = render(<Kartu name="x" km="1" durasi="1:00" trimp={1} size="lg" />);
        // Without polyline RouteGlyph falls back to the bunny glyph variant.
        expect(container.querySelector('[data-variant="glyph"]')).not.toBeNull();
    });

    it('does not render badge pips at the compact (md) size', () => {
        render(<Kartu name="x" km="1" durasi="1:00" trimp={1} badges={['negative_split']} size="md" />);
        // No overlay at compact size — badges are not shown.
        expect(screen.queryByText('Negative Split')).toBeNull();
    });

    it('shows badge pips (name only, no description) in the art overlay at the full (lg) size', () => {
        render(
            <Kartu
                name="x"
                km="1"
                durasi="1:00"
                trimp={1}
                badges={['negative_split']}
                flavor="Some quote."
                size="lg"
            />,
        );
        expect(screen.getByText('Negative Split')).toBeInTheDocument();
        // Description lives in the title attribute, not visible DOM text.
        expect(screen.queryByText(/malah lebih ngebut/)).toBeNull();
    });

    it('shows the flavor quote in the art overlay only on the full tier', () => {
        const { rerender } = render(
            <Kartu name="x" km="1" durasi="1:00" trimp={1} flavor="Comeback paruh kedua." size="md" />,
        );
        expect(screen.queryByText(/Comeback paruh kedua/)).toBeNull();
        rerender(<Kartu name="x" km="1" durasi="1:00" trimp={1} flavor="Comeback paruh kedua." size="lg" />);
        expect(screen.getByText(/Comeback paruh kedua/)).toBeInTheDocument();
    });

    it('exposes the mood via the TRIMP badge aria-label', () => {
        render(<Kartu name="x" km="1" durasi="1:00" trimp={1} mood="nyala" size="lg" />);
        // Mood rides on the TRIMP "power" badge pip as an accessible label.
        expect(screen.getByLabelText('Vibe Nyala')).toBeInTheDocument();
    });

    it('shows a mood pip with aria-label but no visible label text on the compact tier', () => {
        render(<Kartu name="x" km="1" durasi="1:00" trimp={1} mood="lemes" size="md" />);
        expect(screen.getByLabelText('Vibe Lemes')).toBeInTheDocument();
        expect(screen.queryByText('Lemes')).toBeNull();
    });

    it.each([
        ['common', '●'],
        ['uncommon', '◆'],
        ['rare', '★'],
        ['epic', '✦'],
        ['legendary', '✺'],
    ] satisfies Array<[Rarity, string]>)('shows the %s set symbol', (rarity, symbol) => {
        render(<Kartu name="x" km="1" durasi="1:00" trimp={1} rarity={rarity} />);
        expect(screen.getByText(symbol)).toBeInTheDocument();
    });

    it('shows a labeled stat grid on the full tier', () => {
        render(
            <Kartu
                name="x"
                km="1"
                durasi="1:00"
                trimp={1}
                size="lg"
                stats={{ pace: '5:30/km', hr: '150 bpm', cadence: '178 spm', fastestKm: '5:02/km' }}
            />,
        );
        // The full tier is a dense, labeled TCG stat block.
        expect(screen.getByText('Pace')).toBeInTheDocument();
        expect(screen.getByText('5:30/km')).toBeInTheDocument();
        expect(screen.getByText('150 bpm')).toBeInTheDocument();
        expect(screen.getByText('Cadence')).toBeInTheDocument();
        expect(screen.getByText('178 spm')).toBeInTheDocument();
        expect(screen.getByText('Best km')).toBeInTheDocument();
    });

    it('renders the HR-zone effort bar when zone data is present', () => {
        const { container } = render(
            <Kartu name="x" km="1" durasi="1:00" trimp={1} size="lg" zonePct={{ Z1: 20, Z2: 50, Z3: 30 }} />,
        );
        // The bar segments carry per-zone titles; the legend shows Z labels.
        expect(container.querySelector('[title="Z2: 50%"]')).not.toBeNull();
        expect(screen.getByText('Z1')).toBeInTheDocument();
    });

    it('omits the HR-zone bar when there is no zone data', () => {
        const { container } = render(<Kartu name="x" km="1" durasi="1:00" trimp={1} size="lg" />);
        expect(container.querySelector('[title^="Z"]')).toBeNull();
    });

    it('shows pace/HR but omits duration on the compact tier', () => {
        render(<Kartu name="x" km="1" durasi="1:00" trimp={1} size="md" stats={{ pace: '5:30/km', hr: '150 bpm' }} />);
        // Compact tiles carry the core run identity (pace + HR) but not duration.
        expect(screen.getByText(/5:30\/km/)).toBeInTheDocument();
        expect(screen.getByText(/150 bpm/)).toBeInTheDocument();
        expect(screen.queryByText(/1:00/)).toBeNull();
    });

    it('renders the badge emoji pip in the art overlay at the full (lg) size', () => {
        render(
            <Kartu
                name="x"
                km="1"
                durasi="1:00"
                trimp={1}
                badges={['negative_split']}
                flavor="Some quote."
                size="lg"
            />,
        );
        expect(screen.getByText('👻')).toBeInTheDocument();
    });

    it('renders the subtitle when provided', () => {
        render(<Kartu name="x" km="1" durasi="1:00" trimp={1} subtitle="Pagi negatif-split" />);
        expect(screen.getByText('Pagi negatif-split')).toBeInTheDocument();
    });

    it('falls back to the slug name as the pip title when a badge has no ability', () => {
        // An unknown slug is in neither BADGE_ABILITY nor BADGE_LABELS, so the
        // title is just the prettified name (no " · ability" suffix).
        render(
            <Kartu name="x" km="1" durasi="1:00" trimp={1} badges={['mystery_move']} size="lg" />,
        );
        const pip = screen.getByText('Mystery Move').closest('span[title]');
        expect(pip).toHaveAttribute('title', 'Mystery Move');
    });

    it('omits the full-tier stat grid when no stat values are present', () => {
        // With no stats and a blank duration, every grid cell is filtered out so
        // StatGrid renders nothing (returns null).
        render(<Kartu name="x" km="1" durasi="" trimp={1} size="lg" />);
        expect(screen.queryByText('Pace')).toBeNull();
        expect(screen.queryByText('Durasi')).toBeNull();
    });

    describe('hover tilt handlers', () => {
        const originalMatchMedia = globalThis.matchMedia;

        afterEach(() => {
            globalThis.matchMedia = originalMatchMedia;
        });

        function mockMatchMedia(reduced: boolean) {
            globalThis.matchMedia = vi.fn().mockReturnValue({
                matches: reduced,
                media: '',
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
            }) as unknown as typeof globalThis.matchMedia;
        }

        it('sets tilt CSS vars on mouse move and clears them on mouse leave', () => {
            mockMatchMedia(false);
            render(<Kartu name="x" km="1" durasi="1:00" trimp={1} />);
            const card = screen.getByRole('img', { name: 'x' });
            // jsdom gives a zero-size rect; the math still runs and writes vars.
            vi.spyOn(card, 'getBoundingClientRect').mockReturnValue({
                left: 0,
                top: 0,
                width: 200,
                height: 280,
                right: 200,
                bottom: 280,
                x: 0,
                y: 0,
                toJSON: () => ({}),
            } as DOMRect);

            fireEvent.mouseMove(card, { clientX: 150, clientY: 70 });
            expect(card.style.getPropertyValue('--tilt-x')).not.toBe('');
            expect(card.style.getPropertyValue('--tilt-y')).not.toBe('');

            fireEvent.mouseLeave(card);
            expect(card.style.getPropertyValue('--tilt-x')).toBe('');
            expect(card.style.getPropertyValue('--tilt-y')).toBe('');
        });

        it('skips the tilt when the user prefers reduced motion', () => {
            mockMatchMedia(true);
            render(<Kartu name="x" km="1" durasi="1:00" trimp={1} />);
            const card = screen.getByRole('img', { name: 'x' });
            fireEvent.mouseMove(card, { clientX: 10, clientY: 10 });
            expect(card.style.getPropertyValue('--tilt-x')).toBe('');
        });
    });
});
