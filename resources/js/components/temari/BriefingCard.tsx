import { motion } from 'framer-motion';
import { Icon } from '@iconify/react';
import { cn } from '@/lib/cn';
import { fadeInUp } from '@/lib/motion';
import TemariMascot from './TemariMascot';
import TemariPeek from './TemariPeek';
import DegradedChip from './DegradedChip';
import type { BriefingResult, RecoveryTone } from '@/types/inertia';

const PEEK_LINES = [
    'Lagi nungguin lari berikutnya nih',
    'Coba liat pace minggu lalu, makin smooth lho',
    'Inget istirahat ya, jangan ngebut terus',
    'Form-mu lagi oke nih, manfaatin~',
    'Tap aku buat reaksi 🌀',
] as const;

interface BriefingCardProps {
    briefing: BriefingResult;
}

export default function BriefingCard({ briefing }: Readonly<BriefingCardProps>) {
    const ruleClass = vibeLeftRule(briefing.vibeState);
    const recoveryClass = recoveryChipClass(briefing.recoveryTone);

    return (
        <motion.div
            variants={fadeInUp}
            initial="hidden"
            animate="visible"
            className={cn(
                'rounded-2xl border border-line bg-surface-warm p-4 shadow-sm sm:p-5',
                // Mood-coded 3px left rule replaces the old pastel swirl.
                'border-l-[3px]',
                ruleClass,
            )}
        >
            <div className="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:gap-5">
                <div className="relative shrink-0">
                    <TemariMascot
                        mood={briefing.mood}
                        sizeClass="h-44 w-44"
                        idle="mood"
                        gazeTracking
                        aria-label={`Temari — mood ${briefing.mood}`}
                    />
                    <TemariPeek lines={PEEK_LINES} />
                </div>

                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-baseline gap-2">
                        <span className="text-xs font-semibold uppercase tracking-wider text-ink-meta dark:text-ink-meta-dark">
                            Briefing Temari
                        </span>
                        <span className="text-xs font-semibold text-ink dark:text-ink-dark">
                            {briefing.vibeEmoji} {briefing.vibeLabel}
                        </span>
                        {briefing.degraded && <DegradedChip />}
                    </div>
                    <p className="mt-2 text-lg font-semibold leading-snug tracking-tight text-ink dark:text-ink-dark">
                        {briefing.headlineLine}
                    </p>
                    <p className="mt-1 text-sm leading-relaxed text-ink-soft dark:text-ink-soft-dark">
                        {briefing.suggestionLine}
                    </p>

                    <div className="mt-3 flex flex-wrap gap-2">
                        <span className={cn('inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold', recoveryClass)}>
                            <Icon icon="mdi:heart-pulse" width={14} height={14} aria-hidden />
                            {briefing.recoveryLabel}
                        </span>
                        {briefing.streakLabel !== null && (
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-surface-elev/70 px-3 py-1 text-xs font-semibold text-ink dark:bg-surface-dark-elev/70 dark:text-ink-dark">
                                <Icon icon="mdi:run" width={14} height={14} aria-hidden />
                                {briefing.streakLabel}
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </motion.div>
    );
}

function vibeLeftRule(state: string): string {
    switch (state) {
        case 'pumped':
        case 'fresh':
        case 'bouncy':
            return 'border-l-brand-500';
        case 'cooked':
        case 'stretched_thin':
            return 'border-l-mood-cooked';
        case 'worn_down':
            return 'border-l-accent-500';
        case 'hibernating':
            return 'border-l-mood-hibernate';
        default:
            return 'border-l-mood-spinning';
    }
}

function recoveryChipClass(tone: RecoveryTone): string {
    switch (tone) {
        case 'positive':
            return 'bg-mood-bouncy/15 text-mood-bouncy';
        case 'warning':
            return 'bg-mood-glow/15 text-mood-glow';
        case 'alert':
            return 'bg-mood-cooked/15 text-mood-cooked';
        default:
            return 'bg-surface-elev/70 text-ink dark:bg-surface-dark-elev/70 dark:text-ink-dark';
    }
}
