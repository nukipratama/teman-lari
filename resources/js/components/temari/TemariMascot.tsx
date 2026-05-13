import { useRef, useState } from 'react';
import { motion, type Variants } from 'framer-motion';
import { cn } from '@/lib/cn';
import { MASCOT_GRADIENT, moodRing, moodSigilColor } from '@/lib/mood';
import { breath, idleByMood, tapReactions } from '@/lib/motion';
import { useGaze } from '@/hooks/useGaze';
import TemariBody from './TemariBody';
import TemariFace from './TemariFace';
import TemariSigil from './TemariSigil';
import type { Mood } from '@/types/inertia';

function resolveIdle(idle: 'none' | 'breath' | 'mood', mood: Mood): Variants | null {
    if (idle === 'none') return null;
    if (idle === 'breath') return breath;
    return idleByMood[mood] ?? breath;
}

function resolveActiveState(playingReaction: boolean, idleVariants: Variants | null): string | undefined {
    if (playingReaction) return 'play';
    if (idleVariants !== null) return 'idle';
    return undefined;
}

interface TemariMascotProps {
    mood: Mood;
    sigilPattern?: string;
    accessory?: string | null;
    sizeClass?: string;
    sigilPixels?: number;
    ringClass?: string;
    idle?: 'none' | 'breath' | 'mood';
    gazeTracking?: boolean;
    interactive?: boolean;
    hoverable?: boolean;
    className?: string;
    'aria-label'?: string;
}

// All character behaviors (idle/gaze/interactive/hoverable) default off so non-hero
// placements (list rows, strips) stay static and cheap.
export default function TemariMascot({
    mood,
    sigilPattern = 'dddd',
    accessory = null,
    sizeClass = 'h-32 w-32',
    sigilPixels = 128,
    ringClass = 'ring-4',
    idle = 'none',
    gazeTracking = false,
    interactive = false,
    hoverable = false,
    className,
    'aria-label': ariaLabel,
}: Readonly<TemariMascotProps>) {
    const color = moodSigilColor(mood);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const gaze = useGaze(wrapperRef, { range: 240, falloff: 200 });

    const [reactionIdx, setReactionIdx] = useState<number | null>(null);
    const playTapReaction = () => {
        setReactionIdx((i) => (i === null ? 0 : (i + 1) % tapReactions.length));
    };

    const idleVariants = resolveIdle(idle, mood);
    const reactionVariants = reactionIdx === null ? null : tapReactions[reactionIdx];
    const playingReaction = reactionVariants !== null;
    const activeVariants = playingReaction ? reactionVariants : idleVariants;
    const activeState = resolveActiveState(playingReaction, idleVariants);

    const hoverProps = hoverable ? { whileHover: { scale: 1.06, rotate: -2 }, whileTap: { scale: 0.96 } } : {};
    const clickProps = interactive
        ? { onClick: playTapReaction, role: 'button', tabIndex: 0, 'aria-pressed': false }
        : {};

    return (
        <motion.div
            ref={wrapperRef}
            {...hoverProps}
            {...clickProps}
            aria-label={ariaLabel}
            className={cn(
                'relative flex items-center justify-center rounded-full',
                ringClass,
                MASCOT_GRADIENT,
                moodRing(mood),
                sizeClass,
                interactive ? 'cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500' : '',
                className,
            )}
        >
            {/* Inner layer carries idle + tap reactions so outer hover scale doesn't fight inner anim. */}
            <motion.div
                className="absolute inset-0 flex items-center justify-center"
                variants={activeVariants ?? undefined}
                animate={activeState}
                /* v8 ignore next 3 — fires from FM's RAF loop; jsdom never invokes it. */
                onAnimationComplete={() => {
                    if (playingReaction) setReactionIdx(null);
                }}
            >
                <TemariBody size={sigilPixels} color={color} className="absolute inset-0 opacity-80" />
                <TemariFace
                    mood={mood}
                    size={sigilPixels}
                    color={color}
                    gaze={gazeTracking ? gaze : { x: 0, y: 0 }}
                    className="absolute inset-0"
                />
                <TemariSigil
                    pattern={sigilPattern}
                    size={sigilPixels}
                    color={color}
                    accessory={accessory}
                    className="absolute inset-0 mix-blend-multiply dark:mix-blend-screen"
                />
            </motion.div>
        </motion.div>
    );
}
