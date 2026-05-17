import { render } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import TemariCharacter from './TemariCharacter';
import { MOOD_VARIANTS } from '@/lib/temariMoodVariants';
import type { Mood } from '@/types/inertia';

const ALL_MOODS: Mood[] = ['glow', 'bouncy', 'wobble', 'squished', 'spinning', 'dim'];

describe('TemariCharacter', () => {
    it.each(ALL_MOODS)('renders without crashing for mood %s', (mood) => {
        const { container } = render(<TemariCharacter mood={mood} />);
        expect(container.querySelector('svg')).toBeInTheDocument();
    });

    it('uses the variant mood colour somewhere in the SVG', () => {
        const { container } = render(<TemariCharacter mood="bouncy" />);
        const expected = MOOD_VARIANTS.bouncy.moodColor;
        const svg = container.querySelector('svg');
        expect(svg?.innerHTML.toLowerCase()).toContain(expected);
    });

    it('renders the towel accessory only on wobble', () => {
        // The towel-around-neck path `M 30 54 Q 50 49` is unique to wobble.
        const wobble = render(<TemariCharacter mood="wobble" />);
        const glow = render(<TemariCharacter mood="glow" />);
        expect(wobble.container.innerHTML).toContain('M 30 54 Q 50 49');
        expect(glow.container.innerHTML).not.toContain('M 30 54 Q 50 49');
    });

    it('renders the medal accessory only on glow', () => {
        const glow = render(<TemariCharacter mood="glow" />);
        const dim = render(<TemariCharacter mood="dim" />);
        // Medal ribbon path is the distinctive `M 56 51 L 60 60 L 56 62 Z`.
        expect(glow.container.innerHTML).toContain('M 56 51 L 60 60 L 56 62 Z');
        expect(dim.container.innerHTML).not.toContain('M 56 51 L 60 60 L 56 62 Z');
    });

    it('renders zzz particles only on dim', () => {
        const dim = render(<TemariCharacter mood="dim" />);
        const glow = render(<TemariCharacter mood="glow" />);
        // Three Z letters in dim mood; none in glow.
        expect(dim.container.querySelectorAll('text').length).toBeGreaterThanOrEqual(3);
        expect(glow.container.innerHTML).not.toMatch(/<text[^>]*>Z<\/text>/);
    });

    it('renders the head + body always (regardless of mood)', () => {
        ALL_MOODS.forEach((mood) => {
            const { container } = render(<TemariCharacter mood={mood} />);
            // Head is the rounded rect at x=26 y=16 width=48 height=36
            expect(container.innerHTML).toContain('width="48"');
            // Body path begins with `M 34 52`
            expect(container.innerHTML).toContain('M 34 52');
        });
    });

    it('respects the size prop', () => {
        const { container } = render(<TemariCharacter mood="glow" size={48} />);
        const svg = container.querySelector('svg');
        expect(svg).toHaveAttribute('width', '48');
        expect(svg).toHaveAttribute('height', '48');
    });

    it('passes className through to the svg', () => {
        const { container } = render(<TemariCharacter mood="glow" className="opacity-50" />);
        expect(container.querySelector('svg')).toHaveClass('opacity-50');
    });

    it('marks the SVG as aria-hidden (decorative)', () => {
        const { container } = render(<TemariCharacter mood="glow" />);
        expect(container.querySelector('svg')).toHaveAttribute('aria-hidden');
    });
});
