// Mirrors App\Services\Run\Metrics\PaceFormatter::format().
export function formatPace(secPerKm: number): string {
    const total = Math.round(secPerKm);
    const m = Math.floor(total / 60);
    const s = total - m * 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
}

// Drops seconds so card-style cells never wrap; use formatDurationHMS for full precision.
export function formatDuration(seconds: number): string {
    const total = Math.round(seconds);
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    return h > 0 ? `${h}j ${m}m` : `${m}m`;
}

export function formatDurationHMS(seconds: number | null | undefined): string {
    if (seconds == null) return '—';
    const total = Math.round(seconds);
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total - h * 3600 - m * 60;
    if (h > 0) {
        return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }
    return `${m}:${s.toString().padStart(2, '0')}`;
}

// "5 menit lalu", "2 jam lalu", "kemarin", "3 hari lalu". Falls back to '—' on null/invalid.
export function formatRelativeId(iso: string | null | undefined, now: Date = new Date()): string {
    if (!iso) return '—';
    const d = new Date(iso);
    const ms = now.getTime() - d.getTime();
    if (!Number.isFinite(ms)) return '—';
    const sec = Math.round(ms / 1000);
    if (sec < 60) return 'baru aja';
    const min = Math.floor(sec / 60);
    if (min < 60) return `${min} menit lalu`;
    const h = Math.floor(min / 60);
    if (h < 24) return `${h} jam lalu`;
    const day = Math.floor(h / 24);
    if (day === 1) return 'kemarin';
    if (day < 7) return `${day} hari lalu`;
    const week = Math.floor(day / 7);
    if (week < 5) return `${week} minggu lalu`;
    return formatIdDate(iso, 'short');
}

export function formatIdDate(iso: string | null, format: 'short' | 'long' = 'short'): string {
    if (!iso) return '—';
    const d = new Date(iso);
    if (format === 'long') {
        return d.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
    }
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'short' });
}
