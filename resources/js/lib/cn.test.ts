import { describe, expect, it } from 'vitest';
import { cn } from './cn';

describe('cn', () => {
    it('joins truthy class names with spaces', () => {
        expect(cn('a', 'b', 'c')).toBe('a b c');
    });

    it('filters out falsy values', () => {
        expect(cn('a', false, 'b', null, undefined, 'c')).toBe('a b c');
    });

    it('returns empty string when all inputs are falsy', () => {
        expect(cn(false, null, undefined)).toBe('');
    });

    it('handles a single class', () => {
        expect(cn('only')).toBe('only');
    });

    it('merges conflicting tailwind utilities so the last one wins', () => {
        expect(cn('px-2', 'px-4')).toBe('px-4');
        expect(cn('text-sm', false, 'text-lg')).toBe('text-lg');
    });
});
