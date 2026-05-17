import { useRef } from 'react';
import { motion, type Variants } from 'framer-motion';
import { cn } from '@/lib/cn';
import { breath, idleByMood } from '@/lib/motion';
import { useGaze } from '@/hooks/useGaze';
import TemariCharacter from './TemariCharacter';
import type { Mood } from '@/types/inertia';

function resolveIdle(idle: 'none' | 'breath' | 'mood', mood: Mood): Variants | null {
    if (idle === 'none') return null;
    if (idle === 'breath') return breath;
    return idleByMood[mood] ?? breath;
}

interface TemariMascotProps {
    mood: Mood;
    sizeClass?: string;
    idle?: 'none' | 'breath' | 'mood';
    /** Pupils follow the cursor (range ~240px from mascot centre). */
    gazeTracking?: boolean;
    className?: string;
    'aria-label'?: string;
}

export default function TemariMascot({
    mood,
    sizeClass = 'h-32 w-32',
    idle = 'none',
    gazeTracking = false,
    className,
    'aria-label': ariaLabel,
}: Readonly<TemariMascotProps>) {
    const wrapperRef = useRef<HTMLDivElement>(null);
    const gaze = useGaze(wrapperRef, { range: 240, falloff: 200, enabled: gazeTracking });

    const idleVariants = resolveIdle(idle, mood);

    return (
        <motion.div
            ref={wrapperRef}
            aria-label={ariaLabel}
            className={cn('relative flex items-center justify-center', sizeClass, className)}
        >
            <motion.div
                className="absolute inset-0 flex items-center justify-center"
                variants={idleVariants ?? undefined}
                animate={idleVariants === null ? undefined : 'idle'}
            >
                <TemariCharacter
                    mood={mood}
                    gaze={gazeTracking ? gaze : { x: 0, y: 0 }}
                    className="h-full w-full"
                />
            </motion.div>
        </motion.div>
    );
}
