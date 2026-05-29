import { twMerge } from 'tailwind-merge';

/**
 * Join truthy class names and resolve conflicting Tailwind utilities so the
 * last one wins. Lets a component ship base utilities that callers override via
 * `className` without depending on fragile CSS source order. Custom theme
 * utilities (e.g. text-ink, mood-*) aren't in tailwind-merge's groups, so they
 * pass through untouched — same as a plain join.
 */
export function cn(...classes: Array<string | false | null | undefined>): string {
    return twMerge(classes.filter(Boolean).join(' '));
}
