<?php

declare(strict_types=1);

namespace App\Services\Run\Metrics;

use App\Models\ActivityDetail;
use App\Models\User;
use App\Models\WeeklySnapshot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

use function count;
use function is_array;

/**
 * Materialises weekly_snapshots rows from the user's activity_details
 * timeline. The dashboard fitness chart and /progress weekly table read
 * from this table; nothing populates it elsewhere in the codebase.
 *
 * Called by the demo seeder after synthetic runs are materialised, and
 * intended to be called by the Strava ingest pipeline after every sync.
 *
 * Conventions:
 *   - "Week ending" is the Sunday at the end of the ISO week.
 *   - One row per (user_id, week_ending). Re-running upserts.
 *   - Weeks span from the user's first analyzed run up to the current week
 *     (inclusive), even if the user took a week off — the EWMA decay during
 *     a rest week is itself a meaningful signal.
 */
class WeeklyAggregator
{
    public function __construct(private readonly TrainingLoad $trainingLoad)
    {
    }

    public function rebuildFor(User $user): int
    {
        $earliest = ActivityDetail::query()
            ->join('activities', 'activities.id', '=', 'activity_details.activity_id')
            ->where('activities.user_id', $user->id)
            ->whereNotNull('activity_details.start_date_local')
            ->min('activity_details.start_date_local');

        if ($earliest === null) {
            return 0;
        }

        $weekEnding = Carbon::parse((string) $earliest)->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $today = Carbon::today()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        $count = 0;
        while ($weekEnding->lte($today)) {
            $this->upsertWeek($user, $weekEnding);
            $weekEnding = $weekEnding->copy()->addWeek();
            $count++;
        }

        return $count;
    }

    private function upsertWeek(User $user, Carbon $weekEnding): void
    {
        $weekStart = $weekEnding->copy()->subDays(6)->startOfDay();

        $details = ActivityDetail::query()
            ->join('activities', 'activities.id', '=', 'activity_details.activity_id')
            ->where('activities.user_id', $user->id)
            ->whereBetween('activity_details.start_date_local', [$weekStart, $weekEnding->copy()->endOfDay()])
            ->select('activity_details.*')
            ->get();

        $distanceKm = round(((float) $details->sum('distance')) / 1000, 1);
        $runs = $details->count();
        $avgDecoupling = $this->averageDecoupling($details);

        $summary = $this->trainingLoad->summary($user, $weekEnding) ?? [];

        WeeklySnapshot::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'week_ending' => $weekEnding->toDateString(),
            ],
            [
                'distance_km' => $distanceKm,
                'runs' => $runs,
                'weekly_trimp' => $summary['weekly_trimp'] ?? 0.0,
                'atl_7d' => $summary['atl_7d'] ?? 0.0,
                'ctl_42d' => $summary['ctl_42d'] ?? 0.0,
                'form' => $summary['form'] ?? 0.0,
                'form_status' => $summary['form_status'] ?? 'optimal',
                'avg_decoupling' => $avgDecoupling,
                'monotony' => $summary['monotony'] ?? 0.0,
                'strain' => $summary['strain'] ?? 0.0,
            ],
        );
    }

    /**
     * @param  Collection<int, ActivityDetail>  $details
     */
    private function averageDecoupling(Collection $details): ?float
    {
        $values = [];
        foreach ($details as $detail) {
            $summary = $detail->stream_summary;
            if (! is_array($summary)) {
                continue;
            }
            if (isset($summary['decoupling_pct']) && is_numeric($summary['decoupling_pct'])) {
                $values[] = (float) $summary['decoupling_pct'];
            }
        }

        if ($values === []) {
            return null;
        }

        return round(array_sum($values) / count($values), 2);
    }
}
