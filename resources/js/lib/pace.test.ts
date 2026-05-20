import { describe, expect, it } from 'vitest';
import { formatDuration, formatDurationHMS, formatIdDate, formatPace, formatRelativeId } from './pace';

describe('formatPace', () => {
    it("formats whole minutes as M'SS\"", () => {
        expect(formatPace(360)).toBe('6:00');
    });

    it('pads seconds to 2 digits', () => {
        expect(formatPace(305)).toBe('5:05');
    });

    it('rounds fractional seconds', () => {
        expect(formatPace(305.49)).toBe('5:05');
        expect(formatPace(305.5)).toBe('5:06');
    });

    it('handles sub-minute pace', () => {
        expect(formatPace(45)).toBe('0:45');
    });
});

describe('formatDuration', () => {
    it('drops seconds for sub-hour durations', () => {
        expect(formatDuration(630)).toBe('10m');
    });

    it('formats hours+ as "Hj Mm"', () => {
        expect(formatDuration(7320)).toBe('2j 2m');
    });

    it('rounds to nearest minute for single-digit-second durations', () => {
        expect(formatDuration(65)).toBe('1m');
    });
});

describe('formatDurationHMS', () => {
    it('returns dash for null/undefined', () => {
        expect(formatDurationHMS(null)).toBe('—');
        expect(formatDurationHMS(undefined)).toBe('—');
    });

    it('formats with hours as H:MM:SS', () => {
        expect(formatDurationHMS(3725)).toBe('1:02:05');
    });

    it('formats without hours as M:SS', () => {
        expect(formatDurationHMS(125)).toBe('2:05');
    });
});

describe('formatIdDate', () => {
    it('returns dash for null', () => {
        expect(formatIdDate(null)).toBe('—');
    });

    it('returns short format by default with weekday', () => {
        const result = formatIdDate('2026-05-11T08:00:00');
        expect(result).toMatch(/^\w+,\s\d{2}\s\w+$/);
    });

    it('returns long format when requested', () => {
        const result = formatIdDate('2026-05-11T08:00:00', 'long');
        expect(result).toMatch(/\d{4}/);
    });
});

describe('formatRelativeId', () => {
    const now = new Date('2026-05-20T12:00:00.000Z');

    it('returns dash for null / undefined', () => {
        expect(formatRelativeId(null, now)).toBe('—');
        expect(formatRelativeId(undefined, now)).toBe('—');
    });

    it('returns dash for non-parseable input', () => {
        expect(formatRelativeId('totally-not-a-date', now)).toBe('—');
    });

    it('returns "baru aja" for under a minute', () => {
        const iso = new Date(now.getTime() - 30 * 1000).toISOString();
        expect(formatRelativeId(iso, now)).toBe('baru aja');
    });

    it('returns minutes for under an hour', () => {
        const iso = new Date(now.getTime() - 5 * 60 * 1000).toISOString();
        expect(formatRelativeId(iso, now)).toBe('5 menit lalu');
    });

    it('returns hours for under a day', () => {
        const iso = new Date(now.getTime() - 3 * 60 * 60 * 1000).toISOString();
        expect(formatRelativeId(iso, now)).toBe('3 jam lalu');
    });

    it('returns "kemarin" for exactly one day', () => {
        const iso = new Date(now.getTime() - 25 * 60 * 60 * 1000).toISOString();
        expect(formatRelativeId(iso, now)).toBe('kemarin');
    });

    it('returns days for under a week', () => {
        const iso = new Date(now.getTime() - 3 * 24 * 60 * 60 * 1000).toISOString();
        expect(formatRelativeId(iso, now)).toBe('3 hari lalu');
    });

    it('returns weeks for under five weeks', () => {
        const iso = new Date(now.getTime() - 14 * 24 * 60 * 60 * 1000).toISOString();
        expect(formatRelativeId(iso, now)).toBe('2 minggu lalu');
    });

    it('falls back to short date for old timestamps', () => {
        const iso = new Date(now.getTime() - 60 * 24 * 60 * 60 * 1000).toISOString();
        expect(formatRelativeId(iso, now)).toMatch(/\w+,\s\d{2}\s\w+/);
    });
});
