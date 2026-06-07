<?php

declare(strict_types=1);

namespace App\Services\Run;

use App\Models\WeeklySnapshot;
use App\Services\Run\Metrics\PaceCalculator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Shapes the dashboard's week-over-week delta strip and the fitness chart series
 * from a chronological run of WeeklySnapshot rows.
 */
class WeekSummaryBuilder
{
    /**
     * @param  Collection<int, WeeklySnapshot>  $weeks  Chronological (oldest → newest).
     * @return array{distance_delta_km: float, runs_delta: int, pace_delta_sec: float|null, this_week_km: float, this_week_runs: int}|null
     */
    public function weekVsLastWeek(Collection $weeks): ?array
    {
        if ($weeks->count() < 2) {
            return null;
        }

        // Both are non-null because count >= 2 (guarded above).
        /** @var WeeklySnapshot $thisWeek */
        $thisWeek = $weeks->last();
        /** @var WeeklySnapshot $lastWeek */
        $lastWeek = $weeks->slice(-2, 1)->first();

        $paceDelta = null;
        $thisPace = $this->weekPaceSecPerKm($thisWeek);
        $lastPace = $this->weekPaceSecPerKm($lastWeek);
        if ($thisPace !== null && $lastPace !== null) {
            $paceDelta = $thisPace - $lastPace;
        }

        return [
            'distance_delta_km' => (float) (($thisWeek->distance_km ?? 0) - ($lastWeek->distance_km ?? 0)),
            'runs_delta' => (int) (($thisWeek->runs ?? 0) - ($lastWeek->runs ?? 0)),
            'pace_delta_sec' => $paceDelta,
            'this_week_km' => (float) ($thisWeek->distance_km ?? 0),
            'this_week_runs' => (int) ($thisWeek->runs ?? 0),
        ];
    }

    public function weekPaceSecPerKm(WeeklySnapshot $snapshot): ?float
    {
        // Real pace: total moving seconds over total distance for the week.
        // moving_time_sec is null for snapshots written before it was tracked;
        // those return null until the next aggregation backfills the column.
        $km = $snapshot->distance_km;

        return PaceCalculator::secPerKm(
            $km === null ? null : $km * 1000,
            $snapshot->moving_time_sec,
        );
    }

    /**
     * @param  Collection<int, WeeklySnapshot>  $rows
     * @return array{labels: array<int, string>, ctl: array<int, ?float>, atl: array<int, ?float>, form: array<int, ?float>, volume: array<int, ?float>}
     */
    public function fitnessChartData(Collection $rows): array
    {
        return [
            'labels' => $rows->map(fn ($r): string => $r->week_ending->toDateString())->all(),
            'ctl' => $rows->pluck('ctl_42d')->all(),
            'atl' => $rows->pluck('atl_7d')->all(),
            'form' => $rows->pluck('form')->all(),
            'volume' => $rows->pluck('distance_km')->all(),
        ];
    }
}
