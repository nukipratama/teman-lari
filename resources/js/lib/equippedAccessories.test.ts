import { describe, expect, it } from 'vitest';
import { ACCESSORY_KEYS, equippedToKeys } from './equippedAccessories';

describe('equippedToKeys', () => {
    it('returns no keys for null/empty equipped sets', () => {
        expect(equippedToKeys(null)).toEqual([]);
        expect(equippedToKeys(undefined)).toEqual([]);
        expect(equippedToKeys({ headband: null, medal: null, pita: false, aura: false })).toEqual([]);
    });

    it('maps each equipped slot to its unlock key', () => {
        expect(
            equippedToKeys({ headband: 'legendaris', medal: 'emas', pita: true, aura: false }),
        ).toEqual([
            ACCESSORY_KEYS.headbandLegendaris,
            ACCESSORY_KEYS.medalGold,
            ACCESSORY_KEYS.weeklyStreak4,
        ]);
    });

    it('maps the lower-tier variants', () => {
        expect(
            equippedToKeys({ headband: 'epik', medal: 'pertama', pita: false, aura: false }),
        ).toEqual([ACCESSORY_KEYS.headbandEpik, ACCESSORY_KEYS.medalFirstPr]);
    });

    it('treats the base ember headband as no overlay', () => {
        expect(equippedToKeys({ headband: 'ember', medal: null, pita: false, aura: false })).toEqual([]);
    });
});
