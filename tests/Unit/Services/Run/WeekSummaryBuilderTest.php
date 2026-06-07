<?php

declare(strict_types=1);

use App\Models\WeeklySnapshot;
use App\Services\Run\WeekSummaryBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->builder = new WeekSummaryBuilder();
});

/** Un-persisted WeeklySnapshot carrying just the fields under test. */
function makeWeek(array $attributes): WeeklySnapshot
{
    return WeeklySnapshot::factory()->make($attributes);
}

describe('weekVsLastWeek', function (): void {
    it('returns null when fewer than 2 snapshots exist', function (): void {
        expect($this->builder->weekVsLastWeek(new Collection([makeWeek([])])))->toBeNull();
    });

    it('computes distance + runs deltas from the last two snapshots', function (): void {
        $weeks = new Collection([
            makeWeek(['distance_km' => 20, 'runs' => 3, 'moving_time_sec' => 7200]),
            makeWeek(['distance_km' => 25, 'runs' => 4, 'moving_time_sec' => 8250]),
        ]);

        // last week 20km/7200s → 360 s/km; this week 25km/8250s → 330 s/km (-30).
        expect($this->builder->weekVsLastWeek($weeks))->toBe([
            'distance_delta_km' => 5.0,
            'runs_delta' => 1,
            'pace_delta_sec' => -30.0,
            'this_week_km' => 25.0,
            'this_week_runs' => 4,
        ]);
    });

    it('only considers the trailing pair when more than two snapshots exist', function (): void {
        $weeks = new Collection([
            makeWeek(['distance_km' => 5, 'runs' => 1, 'moving_time_sec' => 1800]),
            makeWeek(['distance_km' => 20, 'runs' => 3, 'moving_time_sec' => 7200]),
            makeWeek(['distance_km' => 25, 'runs' => 4, 'moving_time_sec' => 8250]),
        ]);

        expect($this->builder->weekVsLastWeek($weeks))->toMatchArray([
            'distance_delta_km' => 5.0,
            'runs_delta' => 1,
            'this_week_km' => 25.0,
        ]);
    });

    it('falls back to null pace_delta when a snapshot lacks moving_time_sec', function (): void {
        $weeks = new Collection([
            makeWeek(['distance_km' => 20, 'runs' => 3, 'moving_time_sec' => null]),
            makeWeek(['distance_km' => 25, 'runs' => 4, 'moving_time_sec' => 8250]),
        ]);

        expect($this->builder->weekVsLastWeek($weeks)['pace_delta_sec'])->toBeNull();
    });

    it('treats null distance / runs as zero in the deltas', function (): void {
        $weeks = new Collection([
            makeWeek(['distance_km' => null, 'runs' => null, 'moving_time_sec' => null]),
            makeWeek(['distance_km' => 25, 'runs' => 4, 'moving_time_sec' => 8250]),
        ]);

        expect($this->builder->weekVsLastWeek($weeks))->toMatchArray([
            'distance_delta_km' => 25.0,
            'runs_delta' => 4,
            'pace_delta_sec' => null,
        ]);
    });
});

describe('weekPaceSecPerKm', function (): void {
    it('returns real pace as seconds per km', function (): void {
        expect($this->builder->weekPaceSecPerKm(makeWeek(['distance_km' => 20, 'moving_time_sec' => 7200])))
            ->toBe(360.0);
    });

    it('returns null when distance is missing', function (): void {
        expect($this->builder->weekPaceSecPerKm(makeWeek(['distance_km' => null, 'moving_time_sec' => 7200])))
            ->toBeNull();
    });

    it('returns null when moving_time_sec is missing', function (): void {
        expect($this->builder->weekPaceSecPerKm(makeWeek(['distance_km' => 20, 'moving_time_sec' => null])))
            ->toBeNull();
    });
});

describe('fitnessChartData', function (): void {
    it('shapes labels + ctl/atl/form/volume series in order', function (): void {
        $weeks = new Collection([
            makeWeek([
                'week_ending' => Carbon::parse('2026-05-04'),
                'ctl_42d' => 40.0, 'atl_7d' => 55.0, 'form' => -15.0, 'distance_km' => 30.0,
            ]),
            makeWeek([
                'week_ending' => Carbon::parse('2026-05-11'),
                'ctl_42d' => 42.0, 'atl_7d' => 50.0, 'form' => -8.0, 'distance_km' => 35.0,
            ]),
        ]);

        expect($this->builder->fitnessChartData($weeks))->toBe([
            'labels' => ['2026-05-04', '2026-05-11'],
            'ctl' => [40.0, 42.0],
            'atl' => [55.0, 50.0],
            'form' => [-15.0, -8.0],
            'volume' => [30.0, 35.0],
        ]);
    });

    it('returns empty series for an empty collection', function (): void {
        expect($this->builder->fitnessChartData(new Collection()))->toBe([
            'labels' => [],
            'ctl' => [],
            'atl' => [],
            'form' => [],
            'volume' => [],
        ]);
    });
});
