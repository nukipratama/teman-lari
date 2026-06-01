import { Head, Link } from '@inertiajs/react';
import AppShell from '@/layouts/AppShell';
import MotionLink from '@/components/MotionLink';
import ConfettiBurst from '@/components/ConfettiBurst';
import Card from '@/components/ui/Card';
import CollectionHeader from '@/components/koleksi/CollectionHeader';
import HeroPanel from '@/components/ui/HeroPanel';
import Kartu from '@/components/card/Kartu';
import PillButton from '@/components/ui/PillButton';
import Temari from '@/components/temari/Temari';
import { cn } from '@/lib/cn';
import { pressShrink } from '@/lib/motion';
import { emberGlowStyle } from '@/lib/styles';
import PageContainer from '@/components/ui/PageContainer';
import { formatDuration, formatIdDate, formatKm } from '@/lib/pace';
import { RARITY_LABELS, RARITY_ORDER, RARITY_POSE, buildCardStats, paceShapeFromDetail, zonePctFromDetail } from '@/lib/runcard';
import { renderBold } from '@/lib/richText';
import { useState, type ReactNode } from 'react';
import AnalysisStatus from '@/components/temari/AnalysisStatus';
import type {
    Activity,
    ActivityDetail,
    AnalysisPayload,
    CardEdition,
    Mood,
    PaginatedResponse,
    Rarity,
    RunCard as RunCardModel,
} from '@/types/inertia';

interface FeaturedCardPayload {
    id: number;
    activity_id: number;
    rarity: Rarity;
    special_move: string;
    mood: Mood;
    badges: string[] | null;
    detail: ActivityDetail | null;
    edition?: CardEdition | null;
    flavor_analysis?: AnalysisPayload;
}

type CardWithRel = RunCardModel & {
    mood: Mood;
    activity: Activity & { detail: ActivityDetail };
};

interface KartuProps {
    cards: PaginatedResponse<CardWithRel>;
    selectedRarity: string | null;
    featuredCard: FeaturedCardPayload | null;
    rarityCounts: Record<Rarity, number>;
}

export default function KoleksiKartu({
    cards,
    selectedRarity,
    featuredCard,
    rarityCounts,
}: Readonly<KartuProps>) {
    const [burstKey, setBurstKey] = useState<string | null>(null);

    const totalKartu = Object.values(rarityCounts).reduce((sum, n) => sum + n, 0);
    const epicCount = rarityCounts.epic + rarityCounts.legendary;
    const eyebrow = `Koleksi · ${totalKartu} kartu · ${epicCount} terbaik`;

    // One flat, newest-first grid (the controller orders by id desc). Filter
    // tabs narrow to a single rarity; otherwise it's the whole collection.
    const grid = cards.data;

    const triggerBurstFor = (rarity: Rarity, id: number) => {
        if (rarity === 'epic' || rarity === 'legendary') {
            setBurstKey(`card-${id}-${Date.now()}`);
        }
    };

    const gridBody: ReactNode =
        grid.length === 0 ? (
            <div className="mt-6">
                <EmptyState />
            </div>
        ) : (
            <div className="mt-6 grid gap-3.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                {grid.map((card) => (
                    <CardCell key={card.id} card={card} onTap={triggerBurstFor} />
                ))}
            </div>
        );

    return (
        <AppShell>
            <Head title="Koleksi · Kartu" />
            <ConfettiBurst burstKey={burstKey} />
            <PageContainer>
                <CollectionHeader
                    active="kartu"
                    eyebrow={eyebrow}
                    headline1="Semua kartu kamu"
                    headline2="dari Temari."
                    activeCount={String(totalKartu)}
                />

                {featuredCard && <SlimBanner featured={featuredCard} />}

                <RarityFilter selected={selectedRarity} counts={rarityCounts} />

                {gridBody}

                {rarityCounts.legendary === 0 && <LegendaryTease />}
            </PageContainer>
        </AppShell>
    );
}

/** Collection highlight hero — same layout as the homepage featured panel. */
function SlimBanner({ featured }: Readonly<{ featured: FeaturedCardPayload }>) {
    const detail = featured.detail;
    const pose = RARITY_POSE[featured.rarity];
    const kartuProps = {
        name: featured.special_move,
        subtitle: detail ? `${detail.name ?? 'Lari'} · ${formatIdDate(detail.start_date_local, 'short')}` : undefined,
        km: formatKm(detail?.distance),
        durasi: detail?.moving_time != null ? formatDuration(detail.moving_time) : '—',
        trimp: detail?.trimp_edwards != null ? String(Math.round(detail.trimp_edwards)) : '—',
        rarity: featured.rarity,
        mood: featured.mood,
        badges: featured.badges ?? [],
        stats: buildCardStats(detail),
        zonePct: zonePctFromDetail(detail),
        polyline: detail?.summary_polyline,
        paceShape: paceShapeFromDetail(detail),
        edition: featured.edition,
        size: 'md' as const,
    };

    return (
        <HeroPanel className="mt-6 min-h-[320px] lg:px-14 lg:py-12">
            <span
                aria-hidden
                className="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full"
                style={emberGlowStyle()}
            />
            <div className="relative grid items-center gap-8 lg:grid-cols-[160px_1fr_40%] lg:gap-10">
                {/* Temari — desktop only */}
                <div className="hidden lg:block">
                    <Temari pose={pose} size={200} />
                </div>

                {/* Quote + CTA */}
                <div>
                    <div className="mb-3 font-mono text-[11px] font-bold uppercase tracking-[0.2em] text-horizon">
                        ★ Highlight minggu ini · {RARITY_LABELS[featured.rarity]}
                    </div>
                    <h2 className="mb-4 font-display text-display-xl text-cream">
                        <em className="italic text-horizon">{featured.special_move}</em>
                    </h2>
                    {featured.flavor_analysis && (
                        <div className="mb-5 max-w-xl">
                            <AnalysisStatus
                                analysis={featured.flavor_analysis}
                                inertiaReloadProps={['featuredCard']}
                                allowReanalyze={false}
                                showTimestamp={false}
                                onSky
                                renderContent={(text) => (
                                    <p className="font-display text-quote-lg italic text-cream">
                                        &ldquo;{renderBold(text)}&rdquo;
                                    </p>
                                )}
                            />
                        </div>
                    )}
                    <Link href={`/kartu/${featured.id}`}>
                        <PillButton tone="horizon">Lihat kartu</PillButton>
                    </Link>
                </div>

                {/* Card — desktop only (tilted) */}
                <div className="hidden lg:block lg:rotate-[4deg]">
                    <Kartu {...kartuProps} className="w-[260px]" />
                </div>

                {/* Mobile: Temari above, card below */}
                <div className="flex flex-col items-center gap-4 lg:hidden">
                    <Temari pose={pose} size={100} animate={false} />
                    <Kartu {...kartuProps} className="w-full max-w-[300px]" />
                </div>
            </div>
        </HeroPanel>
    );
}

function RarityFilter({
    selected,
    counts,
}: Readonly<{ selected: string | null; counts: Record<Rarity, number> }>) {
    return (
        <nav aria-label="Filter kartu" className="mt-8 flex flex-wrap items-center gap-2">
            <span className="mr-1.5 font-mono text-[11px] uppercase tracking-[0.14em] text-ink-3">
                Tingkat
            </span>
            <FilterPill href="/kartu" label="Semua" active={selected === null} dot={null} />
            {RARITY_ORDER.map((r) => (
                <FilterPill
                    key={r}
                    href={`/kartu?rarity=${r}`}
                    label={`${RARITY_LABELS[r]} · ${counts[r]}`}
                    active={selected === r}
                    dot={r}
                />
            ))}
        </nav>
    );
}

const RARITY_DOT: Record<Rarity, string> = {
    common: 'bg-rarity-common',
    uncommon: 'bg-rarity-uncommon',
    rare: 'bg-rarity-rare',
    epic: 'bg-rarity-epic',
    legendary: 'bg-rarity-legendary',
};

function FilterPill({
    href,
    label,
    active,
    dot,
}: Readonly<{ href: string; label: string; active: boolean; dot: Rarity | null }>) {
    return (
        <MotionLink
            href={href}
            whileTap={pressShrink}
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-medium transition',
                active
                    ? 'bg-sky text-cream font-semibold'
                    : 'bg-sky/[0.06] text-ink-2 hover:bg-sky/[0.12]',
            )}
        >
            {dot && <span aria-hidden className={cn('h-2 w-2 rounded-full', RARITY_DOT[dot])} />}
            {label}
        </MotionLink>
    );
}

function CardCell({
    card,
    onTap,
}: Readonly<{ card: CardWithRel; onTap: (rarity: Rarity, id: number) => void }>) {
    const detail = card.activity?.detail;
    if (!detail) return null;
    const km = formatKm(detail.distance);
    const durasi = detail.moving_time != null ? formatDuration(detail.moving_time) : '—';
    const trimp = detail.trimp_edwards != null ? String(Math.round(detail.trimp_edwards)) : '—';
    const subtitle = `${detail.name ?? 'Lari'} · ${formatIdDate(detail.start_date_local, 'short')}`;

    return (
        <MotionLink
            href={`/kartu/${card.id}`}
            whileTap={pressShrink}
            onClick={() => onTap(card.rarity, card.id)}
            className="mx-auto block w-full max-w-[300px]"
        >
            <Kartu
                name={card.special_move}
                subtitle={subtitle}
                km={km}
                durasi={durasi}
                trimp={trimp}
                rarity={card.rarity}
                mood={card.mood}
                badges={card.badges ?? []}
                stats={buildCardStats(detail)}
                zonePct={zonePctFromDetail(detail)}
                polyline={detail.summary_polyline}
                paceShape={paceShapeFromDetail(detail)}
                edition={card.edition}
                size="md"
            />
        </MotionLink>
    );
}

function EmptyState() {
    return (
        <Card tone="empty" padding="lg" className="mt-8 text-center">
            <p className="font-display text-2xl italic text-ink-2">
                Belum ada kartu di sini.
            </p>
            <p className="mt-2 font-sans text-sm text-ink-2">Coba filter lain, atau sync lari terbaru dulu.</p>
        </Card>
    );
}

function LegendaryTease() {
    return (
        <Card tone="empty" as="section" padding="lg" className="mt-8 flex flex-col items-start gap-5 sm:flex-row sm:items-center">
            <div className="flex h-28 w-20 items-center justify-center rounded-lg border-2 border-dashed border-rarity-legendary bg-rarity-legendary/[0.06] font-display text-4xl italic text-rarity-legendary">
                ?
            </div>
            <div className="flex-1">
                <div className="mb-1.5 font-mono text-[11px] font-bold uppercase tracking-[0.16em] text-rarity-legendary">
                    ★ Legendaris · belum kebuka
                </div>
                <p className="mb-1.5 font-display text-2xl leading-tight tracking-[-0.01em] text-ink">
                    &ldquo;Ada kartu Legendaris nungguin di sini.&rdquo; ✨
                </p>
                <p className="font-display text-sm italic leading-relaxed text-ink-2">
                    Buat buka: PR di 21K, atau 5 lari Nyala beruntun.
                </p>
            </div>
        </Card>
    );
}

