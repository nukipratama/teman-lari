import { describe, expect, it } from 'vitest';
import {
    BADGE_ABILITY,
    BADGE_LABELS,
    RARITY_LABELS,
    RARITY_ORDER,
    avgCadenceFromDetail,
    badgeEmblem,
    badgeName,
    fastestKmFromDetail,
    zonePctFromDetail,
} from './runcard';
import type { ActivityDetail } from '@/types/inertia';

function detailWith(summary: ActivityDetail['stream_summary']): ActivityDetail {
    return {
        id: 1,
        activity_id: 1,
        name: null,
        start_date_local: null,
        distance: null,
        moving_time: null,
        average_heartrate: null,
        trimp_edwards: null,
        stream_summary: summary,
    };
}

describe('RARITY_LABELS', () => {
    it('has label for every rarity in RARITY_ORDER', () => {
        RARITY_ORDER.forEach((r) => {
            expect(RARITY_LABELS[r]).toBeTruthy();
        });
    });

    it('contains all 5 rarities', () => {
        expect(RARITY_ORDER).toHaveLength(5);
    });

    // Parity guard: mirrored in App\Enums\Rarity::label() (see RarityTest.php).
    // Changing the ladder on one runtime without the other fails a test.
    it('exposes the Indonesian rarity ladder labels', () => {
        expect(RARITY_LABELS).toEqual({
            common: 'Biasa',
            uncommon: 'Berkesan',
            rare: 'Langka',
            epic: 'Luar Biasa',
            legendary: 'Legendaris',
        });
    });
});

const BADGE_KEYS = ['hari_panas', 'pejuang_hujan', 'anak_pagi', 'long_slow_distance', 'negative_split', 'tahan_diri'];

describe('BADGE_LABELS', () => {
    it('has expected badge keys', () => {
        BADGE_KEYS.forEach((key) => {
            expect(BADGE_LABELS[key]).toBeTruthy();
        });
    });

    // The two English names are running terms (code-switch rule); the rest are ID-first.
    it('uses the ID-first casual names', () => {
        expect(BADGE_LABELS.hari_panas).toBe('🔥 Tahan Gerah');
        expect(BADGE_LABELS.tahan_diri).toBe('🧘 Anti Kalap');
        expect(BADGE_LABELS.negative_split).toBe('👻 Negative Split');
    });
});

describe('BADGE_ABILITY', () => {
    it('has a one-line meaning for every badge, with no em-dashes', () => {
        BADGE_KEYS.forEach((key) => {
            expect(BADGE_ABILITY[key]).toBeTruthy();
            expect(BADGE_ABILITY[key]).not.toContain('—');
        });
    });
});

describe('badgeEmblem / badgeName', () => {
    it('splits the emoji from the name', () => {
        expect(badgeEmblem('hari_panas')).toBe('🔥');
        expect(badgeName('hari_panas')).toBe('Tahan Gerah');
        expect(badgeName('tahan_diri')).toBe('Anti Kalap');
    });

    it('falls back to prettyBadge for unknown slugs', () => {
        expect(badgeEmblem('unknown_slug')).toBe('');
        expect(badgeName('unknown_slug')).toBe('Unknown Slug');
    });
});

describe('avgCadenceFromDetail', () => {
    it('averages per-km cadence, rounding, ignoring missing values', () => {
        const detail = detailWith({
            per_km: [
                { km: 1, pace: '6:00', avg_cadence_spm: 176 },
                { km: 2, pace: '5:50', avg_cadence_spm: 180 },
                { km: 3, pace: '5:40', avg_cadence_spm: null },
            ],
        });
        expect(avgCadenceFromDetail(detail)).toBe(178);
    });

    it('returns null when no cadence data exists', () => {
        expect(avgCadenceFromDetail(detailWith({ per_km: [{ km: 1, pace: '6:00' }] }))).toBeNull();
        expect(avgCadenceFromDetail(detailWith(undefined))).toBeNull();
    });
});

describe('fastestKmFromDetail', () => {
    it('returns the fastest single-km pace string', () => {
        const detail = detailWith({
            per_km: [
                { km: 1, pace: '6:00' },
                { km: 2, pace: '5:12' },
                { km: 3, pace: '5:45' },
            ],
        });
        expect(fastestKmFromDetail(detail)).toBe('5:12');
    });

    it('returns null without per-km data', () => {
        expect(fastestKmFromDetail(detailWith(undefined))).toBeNull();
    });
});

describe('zonePctFromDetail', () => {
    it('returns the zone distribution when present', () => {
        const detail = detailWith({ time_in_zone_pct: { Z1: 10, Z2: 50, Z3: 40 } });
        expect(zonePctFromDetail(detail)).toEqual({ Z1: 10, Z2: 50, Z3: 40 });
    });

    it('returns null when zone data is absent or all-zero', () => {
        expect(zonePctFromDetail(detailWith(undefined))).toBeNull();
        expect(zonePctFromDetail(detailWith({ time_in_zone_pct: { Z1: 0, Z2: 0 } }))).toBeNull();
    });
});
