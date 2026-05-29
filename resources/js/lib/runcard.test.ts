import { describe, expect, it } from 'vitest';
import { BADGE_LABELS, RARITY_LABELS, RARITY_ORDER } from './runcard';

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

describe('BADGE_LABELS', () => {
    it('has expected badge keys', () => {
        const expected = ['hari_panas', 'pejuang_hujan', 'anak_pagi', 'long_slow_distance', 'negative_split', 'tahan_diri'];
        expected.forEach((key) => {
            expect(BADGE_LABELS[key]).toBeTruthy();
        });
    });
});
