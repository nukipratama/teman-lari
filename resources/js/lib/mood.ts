import type { Mood } from '@/types/inertia';

// PHP keeps original mood constants (`wobble`, `dim`); Hutan Pagi tokens rename
// to `cooked` / `hibernate`. moodToken() bridges so Tailwind classes resolve.

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
            return '#f4a93b';
        case 'bouncy':
            return '#f08a6a';
        case 'wobble':
            return '#c84f4f';
        case 'squished':
            return '#e2783c';
        case 'spinning':
            return '#6e8aaf';
        case 'dim':
        default:
            return '#8a8478';
    }
}

export function moodRing(mood: Mood): string {
    const token = moodToken(mood);
    return `ring-mood-${token}/60`;
}

export const MASCOT_GRADIENT = 'bg-gradient-to-br from-brand-100 to-brand-300 dark:from-brand-700 dark:to-brand-500';
