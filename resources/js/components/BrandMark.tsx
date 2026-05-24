import { cn } from '@/lib/cn';

interface BrandMarkProps {
    size?: 'hero' | 'compact';
    /** Wordmark color tone — flip to 'cream' when the mark sits on a dark hero surface. */
    tone?: 'ink' | 'cream';
    tagline?: boolean;
    className?: string;
}

/**
 * Bunny-head glyph + italic "TemanLari" wordmark, per the Daybreak design.
 * Geometry mirrors the prototype `Logo` atom in
 * `temanlari/project/daybreak-atoms.jsx`:
 *  - rounded square (cream on sky, ink on cream) as the head silhouette
 *  - two ears poking up, slightly rotated outward
 *  - horizon-orange headband stripe across the lower-mid face
 */
export default function BrandMark({ size = 'hero', tone = 'ink', tagline = false, className }: Readonly<BrandMarkProps>) {
    const glyphPx = size === 'compact' ? 28 : 56;
    const wordPx = size === 'compact' ? 22 : 44;
    const wordColor = tone === 'cream' ? 'text-cream' : 'text-ink';

    return (
        <div className={cn('flex items-center gap-2.5', size === 'hero' && 'flex-col gap-3 text-center', className)}>
            <BunnyGlyph size={glyphPx} tone={tone} />
            <div className="flex flex-col items-center">
                <span
                    className={cn('font-display italic leading-none tracking-[-0.02em]', wordColor)}
                    style={{ fontSize: wordPx }}
                >
                    TemanLari
                </span>
                {tagline && size === 'hero' && (
                    <span
                        className={cn(
                            'mt-2 font-display italic',
                            tone === 'cream' ? 'text-cream/70' : 'text-ink-2',
                        )}
                    >
                        Setiap Langkah Berarti
                    </span>
                )}
            </div>
        </div>
    );
}

function BunnyGlyph({ size, tone }: Readonly<{ size: number; tone: 'ink' | 'cream' }>) {
    const face = tone === 'cream' ? 'var(--color-cream)' : 'var(--color-ink)';
    const band = 'var(--color-horizon)';
    const r = size * 0.28;

    return (
        <span
            aria-hidden
            className="relative inline-flex shrink-0"
            style={{ width: size, height: size }}
        >
            <span
                aria-hidden
                className="absolute"
                style={{
                    top: -size * 0.18,
                    left: size * 0.16,
                    width: size * 0.18,
                    height: size * 0.32,
                    background: face,
                    borderRadius: '50%',
                    transform: 'rotate(-12deg)',
                }}
            />
            <span
                aria-hidden
                className="absolute"
                style={{
                    top: -size * 0.18,
                    right: size * 0.16,
                    width: size * 0.18,
                    height: size * 0.32,
                    background: face,
                    borderRadius: '50%',
                    transform: 'rotate(12deg)',
                }}
            />
            <span
                aria-hidden
                className="relative block w-full"
                style={{
                    height: size,
                    background: face,
                    borderRadius: r,
                    overflow: 'hidden',
                }}
            >
                <span
                    aria-hidden
                    className="absolute inset-x-0"
                    style={{
                        top: size * 0.22,
                        height: size * 0.14,
                        background: band,
                    }}
                />
            </span>
        </span>
    );
}
