import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import StatusBarScrim from './StatusBarScrim';

describe('StatusBarScrim', () => {
    it('is decorative and never intercepts taps', () => {
        render(<StatusBarScrim />);
        const scrim = screen.getByTestId('status-bar-scrim');
        expect(scrim).toHaveAttribute('aria-hidden');
        expect(scrim.className).toContain('pointer-events-none');
    });

    // The whole point: it has to outrank the modals, which sit at z-50/51 and
    // would otherwise put their own scrim under the forced-white status glyphs.
    it('sits above the modal layer', () => {
        render(<StatusBarScrim />);
        expect(screen.getByTestId('status-bar-scrim').className).toContain('z-[70]');
    });

    // A gradient, not a solid strip. A hard dark edge above cream content is
    // exactly the band this whole thread started from; fading it out means
    // there is no edge to notice, and it is why the top bar could go back to
    // cream — the contrast for the white glyphs comes from here now.
    it('fades out rather than ending in a hard edge', () => {
        render(<StatusBarScrim />);
        const className = screen.getByTestId('status-bar-scrim').className;
        expect(className).toContain('bg-gradient-to-b');
        expect(className).toContain('from-sky');
        expect(className).toContain('to-transparent');
    });

    it('takes its height from the safe-area inset, so it collapses off-device', () => {
        render(<StatusBarScrim />);
        expect(screen.getByTestId('status-bar-scrim')).toHaveClass(
            'h-[calc(env(safe-area-inset-top)+14px)]',
        );
    });
});
