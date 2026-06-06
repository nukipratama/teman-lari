import type { EquippedAccessories } from '@/types/inertia';
import type { TemariEquipped } from '@/components/temari/TemariProto';

/**
 * Canonical unlock keys from config/temari_unlocks.php. Shared by the mascot's
 * accessory overlays (TemariCharacter) and the equipped→keys conversion below
 * so the two can't drift.
 */
export const ACCESSORY_KEYS = {
    ikatKepalaLegendaris: 'accessory.ikat_kepala_legendaris',
    ikatKepalaEpik: 'accessory.ikat_kepala_epik',
    ikatKepalaLangka: 'accessory.ikat_kepala_langka',
    ikatKepalaBerkesan: 'accessory.ikat_kepala_berkesan',
    medalPertama: 'accessory.medal_pertama',
    medalEmas: 'accessory.medal_emas',
    medalPerak: 'accessory.medal_perak',
    medalPlatina: 'accessory.medal_platina',
    pitaKonsisten: 'accessory.pita_konsisten',
    pitaJarak: 'accessory.pita_jarak',
    pitaMalam: 'accessory.pita_malam',
    pitaMaraton: 'accessory.pita_maraton',
    kausPemula: 'accessory.kaus_pemula',
    kausPagi: 'accessory.kaus_pagi',
    kausHujan: 'accessory.kaus_hujan',
    kausLegendaris: 'accessory.kaus_legendaris',
    celanaRingan: 'accessory.celana_ringan',
    celanaJarak: 'accessory.celana_jarak',
    celanaSplit: 'accessory.celana_split',
    celanaMaraton: 'accessory.celana_maraton',
    sepatuBasic: 'accessory.sepatu_basic',
    sepatuCepat: 'accessory.sepatu_cepat',
    sepatuTahan: 'accessory.sepatu_tahan',
    sepatuLegendaris: 'accessory.sepatu_legendaris',
    auraPemanasan: 'accessory.aura_pemanasan',
    auraGerah: 'accessory.aura_gerah',
    auraTenang: 'accessory.aura_tenang',
    auraJagoan: 'accessory.aura_jagoan',
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

    for (const value of Object.values(equipped)) {
        if (typeof value === 'string' && value.length > 0) {
            keys.push(value);
        }
    }

    return keys;
}

// ── Server unlock key → TemariEquipped variant mappers ─────────────
//
// Single source of truth for mapping the server-side unlock key strings
// (e.g. `accessory.ikat_kepala_legendaris`) to the typed TemariEquipped
// variants (e.g. `legendaris`). Shared by Temari.tsx, Aksesori.tsx, and
// AksesoriUnlockModal.tsx.

export function mapHeadband(key: string | null): TemariEquipped['headband'] {
    if (!key) return null;
    if (key.includes('legendaris')) return 'legendaris';
    if (key.includes('epik')) return 'epik';
    if (key.includes('langka')) return 'epik';
    if (key.includes('berkesan')) return 'ember';
    return 'ember';
}

export function mapMedal(key: string | null): TemariEquipped['medal'] {
    if (!key) return 'none';
    if (key.includes('platina')) return 'platina';
    if (key.includes('perak')) return 'perak';
    if (key.includes('emas')) return 'emas';
    return 'pertama';
}

export function mapPita(key: string | null): TemariEquipped['pita'] {
    if (!key) return null;
    if (key.includes('maraton')) return 'maraton';
    if (key.includes('malam')) return 'malam';
    if (key.includes('jarak')) return 'jarak';
    return 'konsisten';
}

export function mapKaus(key: string | null): TemariEquipped['kaus'] {
    if (!key) return null;
    if (key.includes('legendaris')) return 'legendaris';
    if (key.includes('hujan')) return 'hujan';
    if (key.includes('pagi')) return 'pagi';
    return 'pemula';
}

export function mapCelana(key: string | null): TemariEquipped['celana'] {
    if (!key) return null;
    if (key.includes('maraton')) return 'maraton';
    if (key.includes('split')) return 'split';
    if (key.includes('jarak')) return 'jarak';
    return 'ringan';
}

export function mapSepatu(key: string | null): TemariEquipped['sepatu'] {
    if (!key) return null;
    if (key.includes('legendaris')) return 'legendaris';
    if (key.includes('tahan')) return 'tahan';
    if (key.includes('cepat')) return 'cepat';
    return 'basic';
}

export function mapAura(key: string | null): TemariEquipped['aura'] {
    if (!key) return null;
    if (key.includes('jagoan')) return 'jagoan';
    if (key.includes('tenang')) return 'tenang';
    if (key.includes('gerah')) return 'gerah';
    return 'pemanasan';
}

/**
 * Converts the full server-side EquippedAccessories payload into a
 * TemariEquipped object for the mascot component. Single call site for
 * the Temari.tsx wrapper.
 */
export function serverToEquipped(ea: EquippedAccessories): TemariEquipped {
    return {
        headband: mapHeadband(ea.ikat_kepala),
        medal: mapMedal(ea.medal),
        pita: mapPita(ea.pita),
        kaus: mapKaus(ea.kaus),
        celana: mapCelana(ea.celana),
        sepatu: mapSepatu(ea.sepatu),
        aura: mapAura(ea.aura),
    };
}

/**
 * Converts a single unlock key into a TemariEquipped that shows only the
 * relevant slot. Used by AksesoriUnlockModal and the Aksesori card previews.
 */
export function keyToPreviewEquipped(key: string): TemariEquipped {
    const base: TemariEquipped = { medal: 'none' };

    if (key.includes('ikat_kepala')) return { ...base, headband: mapHeadband(key) };
    if (key.includes('medal')) return { medal: mapMedal(key) };
    if (key.includes('pita')) return { ...base, pita: mapPita(key) };
    if (key.includes('kaus')) return { ...base, kaus: mapKaus(key) };
    if (key.includes('celana')) return { ...base, celana: mapCelana(key) };
    if (key.includes('sepatu')) return { ...base, sepatu: mapSepatu(key) };
    if (key.includes('aura')) return { ...base, aura: mapAura(key) };
    return { headband: 'epik' };
}
