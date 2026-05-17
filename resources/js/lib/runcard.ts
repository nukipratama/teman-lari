import type { Rarity } from '@/types/inertia';

export const RARITY_LABELS: Record<Rarity, string> = {
    biasa: 'Common',
    jarang: 'Uncommon',
    langka: 'Rare',
    epik: 'Epic',
    legendaris: 'Legendary',
};

export const RARITY_ORDER: Rarity[] = ['biasa', 'jarang', 'langka', 'epik', 'legendaris'];

export const BADGE_LABELS: Record<string, string> = {
    hari_panas: '🔥 Heat Beater',
    pejuang_hujan: '🌧️ Rainmaker',
    anak_pagi: '🌅 Early Bird',
    long_slow_distance: '🐢 Long Haul',
    negative_split: '👻 Negative Split',
    tahan_diri: '🧘 Hold Back',
};
