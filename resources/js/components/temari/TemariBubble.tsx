import { cn } from '@/lib/cn';
import TemariMascot from './TemariMascot';
import type { Mood, StoryLine } from '@/types/inertia';

interface TemariBubbleProps {
    line: StoryLine | null;
    size?: 'sm' | 'lg';
    variations?: string[];
    className?: string;
}

export default function TemariBubble({
    line,
    size = 'lg',
    variations = [],
    className,
}: Readonly<TemariBubbleProps>) {
    const mood: Mood = line?.mood ?? 'dim';
    const primary = line?.speech ?? 'Hai! Temari belum punya cerita untuk aktivitas ini.';
    // BE sometimes seeds the first variation with the primary speech — dedupe to avoid duplicates.
    const altTakes = variations.filter((v) => v !== primary);

    const isLarge = size === 'lg';
    const mascotSizeClass = isLarge ? 'h-36 w-36 shrink-0' : 'h-20 w-20 shrink-0';
    const bodyPad = isLarge ? 'p-5' : 'p-3';
    const bodyText = isLarge ? 'text-base' : 'text-sm';

    return (
        <div
            className={cn(
                'flex items-start gap-4 rounded-2xl border border-line bg-surface-elev dark:border-line-dark dark:bg-surface-dark-elev',
                bodyPad,
                className,
            )}
        >
            <TemariMascot
                mood={mood}
                sizeClass={mascotSizeClass}
                idle={isLarge ? 'mood' : 'breath'}
                gazeTracking={isLarge}
                aria-label={`Temari mood ${mood}`}
            />
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <span className="text-sm font-semibold tracking-tight text-ink dark:text-ink-dark">Temari</span>
                    <span className="text-[10px] uppercase tracking-wider text-ink-meta dark:text-ink-meta-dark">
                        {mood}
                    </span>
                </div>
                <p className={cn('mt-1 leading-relaxed text-ink dark:text-ink-dark', bodyText)}>{primary}</p>
                {altTakes.length > 0 && (
                    <ul className="mt-3 space-y-1.5 border-t border-line pt-3 text-sm leading-relaxed text-ink-soft dark:border-line-dark dark:text-ink-soft-dark">
                        {altTakes.map((take) => (
                            <li key={take} className="flex items-start gap-2">
                                <span aria-hidden className="mt-1 inline-block h-1 w-1 shrink-0 rounded-full bg-brand-500/60" />
                                <span>{take}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
}
