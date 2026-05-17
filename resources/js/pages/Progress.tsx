import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Icon } from '@iconify/react';
import { cn } from '@/lib/cn';
import { formatDurationHMS, formatIdDate } from '@/lib/pace';
import AppShell from '@/layouts/AppShell';
import SectionHeading from '@/components/SectionHeading';
import { fadeInUp } from '@/lib/motion';
import { ICON_TONE, type Tone } from '@/lib/tones';
import type { FormStatus, PersonalRecord, WeeklySnapshot } from '@/types/inertia';

interface ExtendedSnapshot extends WeeklySnapshot {
    weekly_trimp: number | null;
    form_status: FormStatus | null;
}

interface ExtendedPR extends Omit<PersonalRecord, 'activity'> {
    value_sec: number;
    set_at: string;
    activity?: { detail?: { name?: string | null } | null };
}

interface ProgressProps {
    snapshots: ExtendedSnapshot[];
    personalRecords: ExtendedPR[];
}

const DISTANCE_CATEGORIES = new Set(['1km', '5km', '10km', '15km', 'half_marathon', 'marathon']);

export default function Progress({ snapshots, personalRecords }: Readonly<ProgressProps>) {
    const latest = snapshots[0] ?? null;
    const prior = snapshots[1] ?? null;

    return (
        <AppShell>
            <Head title="Catatan" />
            <motion.main
                variants={fadeInUp}
                initial="hidden"
                animate="visible"
                className="w-full px-6 py-10"
            >
                <header className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Catatan</h1>
                    <p className="mt-1 text-sm leading-relaxed text-ink-soft">
                        Ringkasan kondisi tubuh + ledger personal best.
                    </p>
                </header>

                {latest !== null && <HeroStats latest={latest} prior={prior} />}

                {snapshots.length > 0 && (
                    <section className="mt-10">
                        <SectionHeading
                            icon="mdi:chart-timeline-variant"
                            title="Riwayat Mingguan"
                            subtitle="Beban + fitness 14 minggu terakhir."
                            tone="brand"
                        />
                        <div className="mt-4 overflow-x-auto rounded-2xl border border-line bg-surface-elev shadow-sm">
                            <table className="w-full text-sm tabular-nums">
                                <thead>
                                    <tr className="border-b border-line text-left text-xs text-ink-meta">
                                        <th className="px-5 py-3 font-semibold">Minggu</th>
                                        <th className="px-5 py-3 font-semibold">Volume</th>
                                        <th className="px-5 py-3 font-semibold">Run</th>
                                        <th className="px-5 py-3 font-semibold">TRIMP</th>
                                        <th className="px-5 py-3 font-semibold">CTL</th>
                                        <th className="px-5 py-3 font-semibold">ATL</th>
                                        <th className="px-5 py-3 font-semibold">Form</th>
                                        <th className="px-5 py-3 font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {snapshots.map((snap, i) => (
                                        <tr
                                            key={snap.id}
                                            className={cn(
                                                'border-b border-line last:border-b-0 transition',
                                                i === 0 ? 'bg-surface-warm/60' : 'hover:bg-surface-sunken/40',
                                            )}
                                        >
                                            <td className="px-5 py-3">
                                                <div className="font-medium text-ink">
                                                    {formatIdDate(snap.week_ending, 'long')}
                                                </div>
                                                {i === 0 && (
                                                    <div className="text-[10px] uppercase tracking-wider text-accent-700">
                                                        Minggu ini
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-ink">
                                                {snap.distance_km != null ? `${snap.distance_km.toFixed(1)} km` : '—'}
                                            </td>
                                            <td className="px-5 py-3 text-ink">{snap.runs ?? '—'}</td>
                                            <td className="px-5 py-3 text-ink">
                                                {snap.weekly_trimp != null ? Math.round(snap.weekly_trimp) : '—'}
                                            </td>
                                            <td className="px-5 py-3 font-medium text-ink">
                                                {snap.ctl_42d != null ? snap.ctl_42d.toFixed(1) : '—'}
                                            </td>
                                            <td className="px-5 py-3 text-ink-soft">
                                                {snap.atl_7d != null ? snap.atl_7d.toFixed(1) : '—'}
                                            </td>
                                            <td className="px-5 py-3 text-ink-soft">
                                                {snap.form != null ? snap.form.toFixed(1) : '—'}
                                            </td>
                                            <td className="px-5 py-3">
                                                <StatusChip status={snap.form_status} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                )}

                <section className="mt-10">
                    <SectionHeading
                        icon="mdi:trophy-variant"
                        title="Personal Records"
                        subtitle="Catatan terbaik kamu — sentuh kartunya buat lihat run aslinya."
                        tone="pop"
                    />
                    {personalRecords.length === 0 ? (
                        <div className="mt-4 rounded-2xl border border-dashed border-line bg-surface-elev/40 p-10 text-center">
                            <Icon icon="mdi:trophy-outline" width={32} height={32} className="mx-auto text-ink-meta" aria-hidden />
                            <p className="mt-3 text-sm leading-relaxed text-ink-soft">
                                Belum ada PR. Run dengan splits + best-effort paces akan otomatis terkumpul di sini.
                            </p>
                        </div>
                    ) : (
                        <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {personalRecords.map((pr) => (
                                <PrCard key={pr.id} pr={pr} />
                            ))}
                        </div>
                    )}
                </section>
            </motion.main>
        </AppShell>
    );
}

// === Hero stats =====================================================

interface HeroStatsProps {
    latest: ExtendedSnapshot;
    prior: ExtendedSnapshot | null;
}

function HeroStats({ latest, prior }: Readonly<HeroStatsProps>) {
    return (
        <section className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <HeroStat
                label="Fitness"
                value={fmt(latest.ctl_42d)}
                delta={delta(latest.ctl_42d, prior?.ctl_42d ?? null)}
                hint="CTL · 42 hari"
                icon="mdi:lightning-bolt"
                tone="brand"
            />
            <HeroStat
                label="Fatigue"
                value={fmt(latest.atl_7d)}
                delta={delta(latest.atl_7d, prior?.atl_7d ?? null)}
                hint="ATL · 7 hari"
                icon="mdi:battery-low"
                tone="accent"
                invertDelta
            />
            <HeroStat
                label="Form"
                value={fmt(latest.form)}
                delta={delta(latest.form, prior?.form ?? null)}
                hint={latest.form_status ?? '—'}
                icon="mdi:scale-balance"
                tone="brand"
            />
            <HeroStat
                label="Volume minggu ini"
                value={latest.distance_km != null ? `${latest.distance_km.toFixed(1)} km` : '—'}
                delta={null}
                hint={latest.runs != null ? `${latest.runs} run` : null}
                icon="mdi:run-fast"
                tone="pop"
            />
        </section>
    );
}

interface HeroStatProps {
    label: string;
    value: string;
    delta: number | null;
    hint?: string | null;
    icon: string;
    tone: Tone;
    /** When true, a positive delta is *bad* (e.g. fatigue rising). */
    invertDelta?: boolean;
}

const HERO_RING_TONE: Record<Tone, string> = {
    brand: 'border-brand-200 bg-brand-50/30',
    accent: 'border-accent-200 bg-accent-50/30',
    pop: 'border-pop-200 bg-pop-50/30',
    neutral: 'border-line bg-surface-sunken/30',
};

function HeroStat({ label, value, delta, hint, icon, tone, invertDelta = false }: Readonly<HeroStatProps>) {
    return (
        <div className={cn('rounded-2xl border p-5 shadow-sm', HERO_RING_TONE[tone])}>
            <div className="flex items-start justify-between">
                <div className="text-xs font-semibold uppercase tracking-wider text-ink-meta">{label}</div>
                <span className={cn('flex h-7 w-7 items-center justify-center rounded-lg', ICON_TONE[tone])} aria-hidden>
                    <Icon icon={icon} width={14} height={14} />
                </span>
            </div>
            <div className="mt-2 flex items-baseline gap-2">
                <span className="text-3xl font-black tabular-nums text-ink">{value}</span>
                {delta !== null && <DeltaChip delta={delta} invert={invertDelta} />}
            </div>
            {hint !== null && hint !== undefined && hint !== '' && (
                <div className="mt-1 text-xs text-ink-meta capitalize">{hint}</div>
            )}
        </div>
    );
}

interface DeltaChipProps {
    delta: number;
    invert: boolean;
}

function DeltaChip({ delta, invert }: Readonly<DeltaChipProps>) {
    if (Math.abs(delta) < 0.05) {
        return <span className="text-xs font-semibold text-ink-meta">±0</span>;
    }
    // Rising fitness = good (green). Rising fatigue = bad (cooked).
    const rising = delta > 0;
    const good = invert ? !rising : rising;
    const sign = rising ? '+' : '';
    const color = good ? 'text-brand-600' : 'text-mood-cooked';
    return (
        <span className={cn('text-xs font-semibold tabular-nums', color)}>
            {sign}
            {delta.toFixed(1)}
        </span>
    );
}

// === Status chip ====================================================

function StatusChip({ status }: Readonly<{ status: FormStatus | null }>) {
    if (status === null) {
        return <span className="text-xs text-ink-meta">—</span>;
    }
    const { label, classes } = statusChipDef(status);
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                classes,
            )}
        >
            {label}
        </span>
    );
}

function statusChipDef(status: FormStatus): { label: string; classes: string } {
    switch (status) {
        case 'fresh':
            return { label: 'Fresh', classes: 'bg-brand-100 text-brand-700' };
        case 'optimal':
            return { label: 'Optimal', classes: 'bg-mood-bouncy/15 text-mood-bouncy' };
        case 'fatigued':
            return { label: 'Fatigued', classes: 'bg-mood-glow/20 text-pop-700' };
        case 'overreaching':
            return { label: 'Overreaching', classes: 'bg-mood-cooked/15 text-mood-cooked' };
    }
}

// === PR card ========================================================

function PrCard({ pr }: Readonly<{ pr: ExtendedPR }>) {
    const card = (
        <div className="group relative h-full overflow-hidden rounded-2xl border border-pop-200 bg-surface-elev p-5 shadow-sm transition hover:border-pop-400 hover:shadow-md">
            <div className="flex items-start justify-between">
                <div>
                    <div className="text-xs font-semibold uppercase tracking-wider text-ink-meta">
                        {prCategoryLabel(pr.category)}
                    </div>
                    <div className="mt-1 text-2xl font-black tabular-nums text-ink">
                        {formatPrValue(pr.category, pr.value_sec)}
                    </div>
                </div>
                <span
                    aria-hidden
                    className="flex h-9 w-9 items-center justify-center rounded-xl bg-pop-100 text-pop-700"
                >
                    <Icon icon="mdi:trophy" width={18} height={18} />
                </span>
            </div>
            <div className="mt-4 truncate text-sm font-medium text-ink-soft">
                {pr.activity?.detail?.name ?? 'Run'}
            </div>
            <div className="mt-0.5 text-xs text-ink-meta">{formatIdDate(pr.set_at, 'long')}</div>
            {/* Decorative pop-color corner ribbon */}
            <span
                aria-hidden
                className="absolute -right-6 -top-6 h-12 w-12 rotate-45 bg-gradient-to-br from-pop-200 to-pop-300 opacity-60"
            />
        </div>
    );

    if (pr.activity_id !== null) {
        return (
            <Link href={`/runs/${pr.activity_id}`} className="block focus:outline-none focus-visible:ring-2 focus-visible:ring-pop-500 rounded-2xl">
                {card}
            </Link>
        );
    }
    return card;
}

function prCategoryLabel(category: string): string {
    const map: Record<string, string> = {
        '1km': '1 KM',
        '5km': '5 KM',
        '10km': '10 KM',
        '15km': '15 KM',
        half_marathon: 'Half Marathon',
        marathon: 'Marathon',
        best_5min: 'Tempo 5 menit',
        best_10min: 'Tempo 10 menit',
        best_20min: 'Tempo 20 menit',
        best_60min: 'Tempo 60 menit',
    };
    return map[category] ?? category;
}

function formatPrValue(category: string, secs: number): string {
    if (DISTANCE_CATEGORIES.has(category)) {
        return formatDurationHMS(secs);
    }
    return `${Math.floor(secs / 60)}:${(secs % 60).toString().padStart(2, '0')}/km`;
}

function fmt(n: number | null): string {
    return n != null ? n.toFixed(1) : '—';
}

function delta(a: number | null, b: number | null): number | null {
    if (a === null || b === null) return null;
    return a - b;
}
