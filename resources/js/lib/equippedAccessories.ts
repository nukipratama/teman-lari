import type { EquippedAccessories } from '@/types/inertia';

/**
 * Canonical unlock keys from config/temari_unlocks.php. Shared by the mascot's
 * accessory overlays (TemariCharacter) and the equipped→keys conversion below
 * so the two can't drift.
 */
export const ACCESSORY_KEYS = {
    headbandLegendaris: 'accessory.headband_legendaris',
    headbandEpik: 'accessory.headband_epik',
    medalFirstPr: 'accessory.medal_first_pr',
    medalGold: 'accessory.medal_gold',
    weeklyStreak4: 'accessory.weekly_streak_4',
} as const;

/**
 * Flattens the resolved equipped set into the unlock keys the mascot overlays
 * key off — one per slot, so the mascot shows exactly what the user equipped
 * (not every accessory they've unlocked).
 */
export function equippedToKeys(equipped: EquippedAccessories | null | undefined): string[] {
    if (!equipped) {
        return [];
    }

    const keys: string[] = [];

    if (equipped.headband === 'legendaris') {
        keys.push(ACCESSORY_KEYS.headbandLegendaris);
    } else if (equipped.headband === 'epik') {
        keys.push(ACCESSORY_KEYS.headbandEpik);
    }

    if (equipped.medal === 'emas') {
        keys.push(ACCESSORY_KEYS.medalGold);
    } else if (equipped.medal === 'pertama') {
        keys.push(ACCESSORY_KEYS.medalFirstPr);
    }

    if (equipped.pita) {
        keys.push(ACCESSORY_KEYS.weeklyStreak4);
    }

    return keys;
}
