import { describe, expect, it } from 'vitest';
import { MOOD_VARIANTS, variantFor } from './temariMoodVariants';
import type { Mood } from '@/types/inertia';

const ALL_MOODS: Mood[] = ['glow', 'bouncy', 'wobble', 'squished', 'spinning', 'dim'];

describe('temariMoodVariants', () => {
    it('exposes a variant for every mood', () => {
        ALL_MOODS.forEach((m) => {
            expect(MOOD_VARIANTS[m]).toBeDefined();
        });
    });

    it('every variant uses a hex moodColor', () => {
        ALL_MOODS.forEach((m) => {
            expect(MOOD_VARIANTS[m].moodColor).toMatch(/^#[0-9a-f]{6}$/i);
        });
    });

    it('variantFor returns the right variant', () => {
        expect(variantFor('glow')).toBe(MOOD_VARIANTS.glow);
        expect(variantFor('spinning')).toBe(MOOD_VARIANTS.spinning);
    });

    it('variantFor falls back to dim for an unknown mood', () => {
        expect(variantFor('unknown' as Mood)).toBe(MOOD_VARIANTS.dim);
    });

    it('every mood declares an accessory + particle slot', () => {
        ALL_MOODS.forEach((m) => {
            expect(MOOD_VARIANTS[m].accessory).not.toBeUndefined();
            expect(MOOD_VARIANTS[m].particles).not.toBeUndefined();
        });
    });

    it('maps moods to their signature accessory + particles', () => {
        expect(MOOD_VARIANTS.glow.accessory).toBe('medal');
        expect(MOOD_VARIANTS.glow.particles).toBe('sparkles');
        expect(MOOD_VARIANTS.dim.accessory).toBe('nightcap');
        expect(MOOD_VARIANTS.dim.particles).toBe('zzz');
        expect(MOOD_VARIANTS.wobble.accessory).toBe('towel');
        expect(MOOD_VARIANTS.wobble.particles).toBe('droplets');
    });
});
