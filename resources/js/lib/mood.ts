import type { Mood } from '@/types/inertia';

// PHP keeps original mood constants (`wobble`, `dim`); current Tailwind
// tokens use `cooked` / `hibernate`. moodToken() bridges so the class
// names resolve.

export const MOOD_FACE: Record<Mood, string> = {
    glow: '✨',
    bouncy: '🦘',
    wobble: '🥵',
    squished: '🍳',
    spinning: '💫',
    dim: '🌧️',
};

export function moodToken(mood: Mood): string {
    switch (mood) {
        case 'glow':
            return 'glow';
        case 'bouncy':
            return 'bouncy';
        case 'wobble':
            return 'cooked';
        case 'squished':
            return 'squished';
        case 'spinning':
            return 'spinning';
        case 'dim':
        default:
            return 'hibernate';
    }
}

export function moodSigilColor(mood: Mood): string {
    switch (mood) {
        case 'glow':
            return '#d99a1a';
        case 'bouncy':
            return '#c83a76';
        case 'wobble':
            return '#b8302f';
        case 'squished':
            return '#c46f1c';
        case 'spinning':
            return '#6b4ea8';
        case 'dim':
        default:
            return '#6e7b72';
    }
}

export function moodRing(mood: Mood): string {
    const token = moodToken(mood);
    return `ring-mood-${token}/60`;
}

