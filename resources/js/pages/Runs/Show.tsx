import { Head, Link } from '@inertiajs/react';
import { Icon } from '@iconify/react';
import { motion } from 'framer-motion';
import AppShell from '@/layouts/AppShell';
import AktivitasDetailPane, {
    type DetailedActivity,
    type DetailedActivityDetail,
    type PastYouMatch,
} from '@/components/aktivitas/AktivitasDetailPane';
import HeroPanel from '@/components/daybreak/HeroPanel';
import MoodChip from '@/components/daybreak/MoodChip';
import TemariProto from '@/components/daybreak/TemariProto';
import { moodFromActivity } from '@/lib/moodFromActivity';
import { fadeInUp } from '@/lib/motion';
import { formatIdDate, formatPace } from '@/lib/pace';
import type { AnalysisPayload, Mood, RunCard as RunCardModel, StoryLine } from '@/types/inertia';

interface RunsShowProps {
    activity: DetailedActivity;
    detail: DetailedActivityDetail;
    card: RunCardModel | null;
    storyLine: StoryLine | null;
    speechAnalysis: AnalysisPayload;
    insightTechnical: AnalysisPayload;
    insightSplits: AnalysisPayload;
    insightZones: AnalysisPayload;
    pastYou: PastYouMatch | null;
}

export default function RunsShow({
    activity,
    detail,
    card,
    storyLine,
    speechAnalysis,
    insightTechnical,
    insightSplits,
    insightZones,
    pastYou,
}: Readonly<RunsShowProps>) {
    const mood: Mood = storyLine?.mood ?? moodFromActivity(detail);
    const km = detail.distance != null ? (detail.distance / 1000).toFixed(2) : '—';
    const paceSec =
        detail.distance != null && detail.moving_time != null && detail.distance > 0
            ? detail.moving_time / (detail.distance / 1000)
            : null;
    const pace = paceSec != null ? formatPace(paceSec) : '—';
    const hr = detail.average_heartrate != null ? Math.round(detail.average_heartrate) : null;
    const trimp = detail.trimp_edwards != null ? Math.round(detail.trimp_edwards) : null;

    return (
        <AppShell>
            <Head title={detail.name ?? 'Run'} />
            <motion.main
                variants={fadeInUp}
                initial="hidden"
                animate="visible"
                className="w-full px-5 py-6 sm:px-8 lg:px-14 lg:py-10"
            >
                <Link
                    href="/aktivitas"
                    className="mb-5 inline-flex items-center gap-1 font-mono text-xs uppercase tracking-[0.12em] text-ink-3 transition hover:text-horizon"
                >
                    <Icon icon="mdi:arrow-left" width={14} height={14} aria-hidden />
                    Semua aktivitas
                </Link>

                <HeroPanel className="lg:px-14 lg:py-12">
                    <span
                        aria-hidden
                        className="pointer-events-none absolute -right-12 -top-12 h-60 w-60 rounded-full"
                        style={{ background: 'radial-gradient(circle, rgba(232,160,118,0.3) 0%, transparent 70%)' }}
                    />
                    <div className="relative grid items-center gap-8 lg:grid-cols-[180px_1fr]">
                        <div className="hidden lg:block">
                            <TemariProto pose="proud" size={180} />
                        </div>
                        <div>
                            <div className="mb-3 flex flex-wrap items-center gap-2">
                                <MoodChip mood={mood} onSky />
                                <span className="font-mono text-[10px] uppercase tracking-[0.14em] text-cream/55">
                                    {formatIdDate(detail.start_date_local, 'long')}
                                </span>
                            </div>
                            <h1 className="mb-6 font-display text-[44px] leading-[0.95] tracking-[-0.015em] text-cream sm:text-[56px] lg:text-[64px]">
                                <em className="italic text-horizon">{detail.name ?? 'Lari'}</em>
                            </h1>
                            <div className="grid grid-cols-2 gap-5 sm:grid-cols-4">
                                <HeroStat label="KM" value={km} />
                                <HeroStat label="Pace" value={pace} />
                                <HeroStat label="HR" value={hr != null ? `${hr}` : '—'} unit="bpm" />
                                <HeroStat label="TRIMP" value={trimp != null ? `${trimp}` : '—'} />
                            </div>
                        </div>
                    </div>
                </HeroPanel>

                <div className="mt-8">
                    <AktivitasDetailPane
                        activity={activity}
                        detail={detail}
                        card={card}
                        storyLine={storyLine}
                        speechAnalysis={speechAnalysis}
                        insightTechnical={insightTechnical}
                        insightSplits={insightSplits}
                        insightZones={insightZones}
                        pastYou={pastYou}
                    />
                </div>
            </motion.main>
        </AppShell>
    );
}

function HeroStat({ label, value, unit }: Readonly<{ label: string; value: string; unit?: string }>) {
    return (
        <div>
            <div className="mb-1.5 font-mono text-[9px] uppercase tracking-[0.14em] text-cream/55">{label}</div>
            <div className="flex items-baseline gap-1.5">
                <span className="font-sans text-3xl font-bold leading-none tabular-nums text-cream sm:text-4xl">
                    {value}
                </span>
                {unit != null && (
                    <span className="font-mono text-[10px] uppercase tracking-[0.12em] text-cream/55">{unit}</span>
                )}
            </div>
        </div>
    );
}
