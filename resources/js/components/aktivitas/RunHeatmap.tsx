import { Link } from '@inertiajs/react';
import { useMemo } from 'react';
import { cn } from '@/lib/cn';

export interface HeatmapCell {
    date: string;
    trimp: number | null;
    distance_km: number | null;
    activity_id: number | null;
}

interface RunHeatmapProps {
    cells: ReadonlyArray<HeatmapCell>;
    className?: string;
}

const WEEKDAY_LABELS = ['S', 'S', 'R', 'K', 'J', 'S', 'M'] as const;

interface PositionedCell extends HeatmapCell {
    /** 0 = first column (oldest week), grows toward the right (today). */
    column: number;
    /** 0 = Monday, 6 = Sunday. Matches WEEKDAY_LABELS order. */
    row: number;
}

/**
 * GitHub-style contribution grid for runs. 7 rows (Mon-Sun) × N columns of
 * ISO-week, colored by TRIMP intensity. Cells with a single resolvable
 * activity become tap targets that jump to the run detail page.
 *
 * Horizontal scroll inside the container at long ranges so wide grids never
 * push the page past the viewport on mobile.
 */
export default function RunHeatmap({ cells, className }: Readonly<RunHeatmapProps>) {
    const positioned = useMemo<PositionedCell[]>(() => layoutCells(cells), [cells]);
    const columns = positioned.length === 0 ? 0 : positioned[positioned.length - 1].column + 1;

    if (positioned.length === 0) {
        return null;
    }

    return (
        <section
            aria-label="Heatmap lari"
            className={cn(
                'overflow-x-auto rounded-2xl border border-line bg-surface-elev p-4 shadow-sm sm:p-5',
                className,
            )}
        >
            <div className="flex items-baseline justify-between gap-3 pb-3">
                <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-meta">
                    Heatmap lari
                </h2>
                <Legend />
            </div>

            <div className="flex gap-2" style={{ minWidth: `${columns * 14 + 24}px` }}>
                <div className="flex flex-col justify-between py-1 text-[10px] text-ink-meta">
                    {WEEKDAY_LABELS.map((label, idx) => (
                        <span key={label + idx} className="h-2.5 leading-none">
                            {idx % 2 === 0 ? label : ''}
                        </span>
                    ))}
                </div>
                <div
                    className="grid grid-flow-col gap-[2px]"
                    style={{
                        gridTemplateRows: 'repeat(7, 0.625rem)',
                        gridTemplateColumns: `repeat(${columns}, 0.625rem)`,
                    }}
                >
                    {positioned.map((cell) => (
                        <HeatmapCellView key={cell.date} cell={cell} />
                    ))}
                </div>
            </div>
        </section>
    );
}

function HeatmapCellView({ cell }: Readonly<{ cell: PositionedCell }>) {
    const bucket = intensityBucket(cell.trimp);
    const title = cellTitle(cell);
    const style = {
        gridRow: cell.row + 1,
        gridColumn: cell.column + 1,
    };

    const className = cn('h-2.5 w-2.5 rounded-[3px] transition', BUCKET_BG[bucket]);

    if (cell.activity_id !== null) {
        return (
            <Link
                href={`/aktivitas/${cell.activity_id}`}
                style={style}
                className={cn(className, 'hover:ring-2 hover:ring-brand-400 hover:ring-offset-1 hover:ring-offset-surface-elev')}
                title={title}
                aria-label={title}
            />
        );
    }

    return <div style={style} className={className} title={title} aria-hidden />;
}

function Legend() {
    return (
        <div className="flex items-center gap-1 text-[10px] text-ink-meta">
            <span>kurang</span>
            {([0, 1, 2, 3, 4] as const).map((bucket) => (
                <span key={bucket} className={cn('h-2.5 w-2.5 rounded-[3px]', BUCKET_BG[bucket])} />
            ))}
            <span>banyak</span>
        </div>
    );
}

const BUCKET_BG: Record<0 | 1 | 2 | 3 | 4, string> = {
    0: 'bg-line/40',
    1: 'bg-brand-200',
    2: 'bg-brand-400',
    3: 'bg-brand-600',
    4: 'bg-accent-500',
};

function intensityBucket(trimp: number | null): 0 | 1 | 2 | 3 | 4 {
    if (trimp === null || trimp <= 0) return 0;
    if (trimp < 30) return 1;
    if (trimp < 80) return 2;
    if (trimp < 150) return 3;
    return 4;
}

function cellTitle(cell: PositionedCell): string {
    if (cell.trimp === null) return cell.date;
    const km = cell.distance_km !== null ? `${cell.distance_km.toFixed(2)} km · ` : '';
    return `${cell.date} · ${km}TRIMP ${cell.trimp}`;
}

function layoutCells(cells: ReadonlyArray<HeatmapCell>): PositionedCell[] {
    if (cells.length === 0) return [];

    const first = parseDate(cells[0].date);
    const firstMonday = startOfWeek(first);

    return cells.map((cell) => {
        const d = parseDate(cell.date);
        const daysFromAnchor = Math.round((d.getTime() - firstMonday.getTime()) / 86_400_000);
        const column = Math.floor(daysFromAnchor / 7);
        const dayIdx = (d.getDay() + 6) % 7; // 0 = Mon, 6 = Sun
        return { ...cell, column, row: dayIdx };
    });
}

function parseDate(iso: string): Date {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d);
}

function startOfWeek(date: Date): Date {
    const d = new Date(date);
    const dayIdx = (d.getDay() + 6) % 7; // Monday = 0
    d.setDate(d.getDate() - dayIdx);
    d.setHours(0, 0, 0, 0);
    return d;
}
