import { AnimatePresence, motion } from 'framer-motion';
import { useRef, useState } from 'react';
import { toPng } from 'html-to-image';
import { Icon } from '@iconify/react';
import BrandMark from '@/components/BrandMark';
import { cn } from '@/lib/cn';
import { iconButtonVariants, toggleButtonVariants } from '@/lib/variants';
import { RARITY_LABELS } from '@/lib/runcard';
import type { Rarity } from '@/types/inertia';

export interface ShareKartuData {
    id: number;
    name: string;
    rarity: Rarity;
    subtitle: string | null;
    date: string | null;
    km: string;
    durasi: string;
    trimp: string;
    hr: string | null;
    location: string | null;
    weather: string | null;
    tags: string[];
    quote: string | null;
}

interface ShareIgModalProps {
    kartu: ShareKartuData | null;
    onClose: () => void;
}

type Theme = 'Dawn' | 'Sky' | 'Cream' | 'Inverted';
type Format = 'story' | 'feed';

const THEME_BG: Record<Theme, React.CSSProperties> = {
    Dawn: {
        background:
            'linear-gradient(170deg, var(--color-sky-deep) 0%, var(--color-sky) 50%, oklch(58% 0.10 38) 88%, var(--color-horizon-deep) 100%)',
        color: 'var(--color-cream)',
    },
    Sky: { background: 'var(--color-sky)', color: 'var(--color-cream)' },
    Cream: { background: 'var(--color-cream-deep)', color: 'var(--color-ink)' },
    Inverted: { background: 'var(--color-sky-deep)', color: 'var(--color-cream)' },
};

const THEMES: Theme[] = ['Dawn', 'Sky', 'Cream', 'Inverted'];

export default function ShareIgModal({ kartu, onClose }: Readonly<ShareIgModalProps>) {
    const [theme, setTheme] = useState<Theme>('Dawn');
    const [showStats, setShowStats] = useState(true);
    const [showQuote, setShowQuote] = useState(true);
    const [format, setFormat] = useState<Format>('story');
    const previewRef = useRef<HTMLDivElement>(null);

    if (kartu === null) return null;

    const isDark = theme !== 'Cream';
    const dividerColor = isDark ? 'rgba(246,241,232,0.18)' : 'rgba(31,39,71,0.10)';
    const metaColor = isDark ? 'rgba(246,241,232,0.72)' : 'var(--color-ink-3)';

    const statItems = [
        { v: kartu.km, l: 'KM' },
        { v: kartu.durasi, l: 'Durasi' },
        { v: kartu.trimp, l: 'TRIMP' },
        ...(kartu.hr ? [{ v: kartu.hr, l: 'HR' }] : []),
        ...(kartu.weather ? [{ v: kartu.weather, l: 'Cuaca' }] : []),
        ...(kartu.location ? [{ v: kartu.location, l: 'Lokasi' }] : []),
    ];

    const captureImage = async (): Promise<Blob> => {
        const dataUrl = await toPng(previewRef.current!, {
            pixelRatio: 3,
            skipFonts: true,   // avoids CORS errors from Google Fonts + cross-origin CSS
        });
        const res = await fetch(dataUrl);
        return res.blob();
    };

    const handleShare = async () => {
        if (previewRef.current && typeof navigator.share === 'function') {
            try {
                const blob = await captureImage();
                const file = new File([blob], `${kartu.name}.png`, { type: 'image/png' });
                if (navigator.canShare?.({ files: [file] })) {
                    await navigator.share({ files: [file], title: `${kartu.name} · TemanLari` });
                    return;
                }
            } catch {
                // fall through to URL share
            }
        }
        const url = `${globalThis.location.origin}/kartu/${kartu.id}`;
        if (typeof navigator.share === 'function') {
            try {
                await navigator.share({
                    title: `${kartu.name} · TemanLari`,
                    text: kartu.quote ?? `Kartu ${RARITY_LABELS[kartu.rarity]}: ${kartu.name}`,
                    url,
                });
            } catch {
                // user cancelled or API unavailable
            }
        } else {
            await navigator.clipboard.writeText(url).catch(() => { /* silent */ });
        }
    };

    const handleCopy = async () => {
        if (!previewRef.current) return;
        try {
            const blob = await captureImage();
            await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
        } catch {
            // clipboard API unavailable — silent
        }
    };

    return (
        <AnimatePresence>
            <motion.div
                key="share-backdrop"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                className="fixed inset-0 z-[51] flex items-center justify-center p-4"
                style={{ background: 'rgba(0,0,0,0.5)', backdropFilter: 'blur(6px)' }}
                onClick={onClose}
            >
                <motion.div
                    key="share-panel"
                    initial={{ opacity: 0, scale: 0.96, y: 8 }}
                    animate={{ opacity: 1, scale: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.96, y: 8 }}
                    transition={{ duration: 0.3, ease: [0.22, 1, 0.36, 1] }}
                    onClick={(e) => e.stopPropagation()}
                    className="flex w-full max-w-lg flex-col overflow-y-auto rounded-3xl bg-cream shadow-2xl lg:max-w-4xl lg:flex-row lg:overflow-hidden"
                    style={{ maxHeight: '92dvh' }}
                >
                    {/* ── LEFT: preview ──
                        Mobile: the whole panel scrolls as one; these halves keep their
                        natural height (shrink-0) so nothing clips. Desktop: side-by-side,
                        preview fixed and the controls column scrolls on its own. */}
                    <div className="flex shrink-0 flex-col items-center gap-3 bg-cream-deep p-5 lg:w-80 lg:overflow-hidden">
                        {/* Preview canvas */}
                        <div
                            ref={previewRef}
                            className={cn(
                                'relative w-full overflow-hidden rounded-2xl',
                                format === 'story' ? 'aspect-[9/16]' : 'aspect-square',
                            )}
                            style={{
                                ...THEME_BG[theme],
                                display: 'flex',
                                flexDirection: 'column',
                                padding: '1.25rem',
                                boxShadow: '0 16px 48px rgba(31,39,71,0.25)',
                            }}
                        >
                            {/* Soft glow behind the hero */}
                            <span
                                aria-hidden
                                className="pointer-events-none absolute left-1/2 top-[44%] h-56 w-56 -translate-x-1/2 -translate-y-1/2 rounded-full"
                                style={{
                                    background: 'radial-gradient(circle, oklch(82% 0.14 55 / 0.36), transparent 66%)',
                                    filter: 'blur(12px)',
                                }}
                            />

                            {/* Brand — absolutely anchored so its scaled box never
                                disturbs the column layout or clips at the edge. */}
                            <div className="absolute right-5 top-5 origin-top-right scale-[0.5]">
                                <BrandMark tone={isDark ? 'cream' : 'ink'} />
                            </div>

                            {/* Header: rarity flag */}
                            <div className="relative">
                                <span
                                    className="inline-block whitespace-nowrap rounded-full px-2.5 py-1 font-mono text-[11px] font-bold uppercase tracking-[0.14em]"
                                    style={{ background: 'var(--color-horizon)', color: 'var(--color-sky-deep)' }}
                                >
                                    ★ {RARITY_LABELS[kartu.rarity]}
                                </span>
                            </div>

                            {/* Hero: name + quote, optically centered to fill the
                                canvas at any aspect ratio (no dead spacer). */}
                            <div className="relative flex flex-1 flex-col justify-center gap-4 py-6">
                                <h3
                                    className={cn(
                                        'font-display italic leading-[0.96] tracking-[-0.02em]',
                                        format === 'story' ? 'text-[40px]' : 'text-[32px]',
                                    )}
                                    style={{ color: isDark ? 'var(--color-horizon)' : 'var(--color-ink)' }}
                                >
                                    {kartu.name}.
                                </h3>
                                {showQuote && kartu.quote && (
                                    <p
                                        className="border-l-2 pl-3.5 font-display text-base italic leading-snug"
                                        style={{
                                            borderColor: 'var(--color-horizon)',
                                            color: isDark ? 'rgba(246,241,232,0.88)' : 'var(--color-ink-2)',
                                        }}
                                    >
                                        {kartu.quote}
                                    </p>
                                )}
                            </div>

                            {/* Footer: stat list + meta line, grounded at the bottom */}
                            <div className="relative">
                                {showStats && (
                                    <div className="border-t pt-3" style={{ borderColor: dividerColor }}>
                                        {(format === 'story' ? statItems : statItems.slice(0, 3)).map(({ v, l }) => (
                                            <div key={l} className="flex items-baseline justify-between gap-3 py-[3px]">
                                                <span
                                                    className="font-mono text-[11px] uppercase tracking-[0.12em]"
                                                    style={{ color: metaColor }}
                                                >
                                                    {l}
                                                </span>
                                                <span className="min-w-0 truncate font-sans text-sm font-bold tabular-nums">
                                                    {v}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                {kartu.date && (
                                    <div
                                        className="mt-3 font-mono text-[11px] tracking-[0.08em]"
                                        style={{ color: metaColor }}
                                    >
                                        {kartu.date.replace('\n', ' · ')}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Format picker */}
                        <div className="grid w-full grid-cols-2 gap-2">
                            {(['story', 'feed'] as Format[]).map((f) => (
                                <button
                                    key={f}
                                    type="button"
                                    onClick={() => setFormat(f)}
                                    aria-pressed={format === f}
                                    className={cn(
                                        'focus-ring rounded-xl p-3 text-xs font-medium transition',
                                        format === f
                                            ? 'border-2 border-horizon bg-cream font-semibold text-ink'
                                            : 'border-2 border-transparent bg-cream text-ink-2 hover:border-cream-deep',
                                    )}
                                >
                                    <div
                                        className={cn(
                                            'mx-auto mb-1.5 rounded-sm bg-sky/25',
                                            f === 'story' ? 'h-7 w-4' : 'h-6 w-6',
                                        )}
                                    />
                                    {f === 'story' ? 'Story · 9:16' : 'Feed · 1:1'}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* ── RIGHT: controls ── */}
                    <div className="flex flex-col max-lg:shrink-0 lg:flex-1 lg:overflow-y-auto">
                        {/* Header */}
                        <div className="flex items-center gap-3 border-b border-cream-deep px-5 pb-3.5 pt-5">
                            <button
                                type="button"
                                onClick={onClose}
                                aria-label="Tutup"
                                className={iconButtonVariants({ size: 'sm' })}
                            >
                                <Icon icon="mdi:close" width={16} height={16} />
                            </button>
                            <div className="flex-1 text-center">
                                <div className="font-mono text-[11px] uppercase tracking-[0.14em] text-ink-3">
                                    Bagikan kartu
                                </div>
                                <div className="font-display text-xl tracking-tight text-ink">
                                    {kartu.name}
                                </div>
                            </div>
                            <div className="w-8" />
                        </div>

                        <div className="flex flex-1 flex-col gap-5 px-5 pb-6 pt-5">
                            {/* Theme */}
                            <div>
                                <div className="mb-2 font-mono text-[11px] font-bold uppercase tracking-[0.12em] text-ink-3">
                                    Tema
                                </div>
                                <div className="flex gap-2">
                                    {THEMES.map((t) => (
                                        <button
                                            key={t}
                                            type="button"
                                            onClick={() => setTheme(t)}
                                            aria-pressed={theme === t}
                                            className={cn(toggleButtonVariants({ selected: theme === t, size: 'md' }), 'flex-1')}
                                        >
                                            {t}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Toggles — the quote only renders on the taller story format */}
                            <div className="overflow-hidden rounded-xl bg-cream-deep">
                                {[
                                    { label: 'Tampilkan data', value: showStats, toggle: () => setShowStats((v) => !v), disabled: false },
                                    { label: 'Tampilkan quote', value: showQuote, toggle: () => setShowQuote((v) => !v), disabled: format === 'feed' },
                                ].map(({ label, value, toggle, disabled }, i) => (
                                    <div
                                        key={label}
                                        className={cn(
                                            'flex items-center justify-between px-4 py-3.5',
                                            i > 0 && 'border-t border-cream',
                                            disabled && 'pointer-events-none opacity-40',
                                        )}
                                    >
                                        <span className="text-sm text-ink">{label}</span>
                                        <button
                                            type="button"
                                            onClick={toggle}
                                            aria-checked={value}
                                            role="switch"
                                            className="focus-ring relative h-5 w-9 shrink-0 rounded-full transition-colors"
                                            style={{ background: value ? 'var(--color-horizon)' : 'rgba(31,39,71,0.15)' }}
                                        >
                                            <span
                                                className="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-all"
                                                style={{ left: value ? '18px' : '2px' }}
                                            />
                                        </button>
                                    </div>
                                ))}
                            </div>

                            <div className="flex-1" />

                            {/* CTAs */}
                            <div className="flex flex-col gap-2">
                                <button
                                    onClick={handleShare}
                                    className="w-full rounded-full bg-horizon-deep py-3.5 font-sans text-sm font-semibold text-white transition-opacity hover:opacity-90"
                                >
                                    Bagikan
                                </button>
                                <button
                                    onClick={handleCopy}
                                    className="w-full rounded-full border border-cream-deep py-3 font-sans text-[13px] font-medium text-ink-2 transition-colors hover:bg-cream-deep"
                                >
                                    Salin Gambar
                                </button>
                            </div>
                        </div>
                    </div>
                </motion.div>
            </motion.div>
        </AnimatePresence>
    );
}
