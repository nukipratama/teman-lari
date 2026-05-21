<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\ActivityDetail;
use App\Models\User;
use App\Services\Gamification\MilestoneDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function buildActivity(User $user, string $startDate, int $distanceM, ?int $movingSec = null): array
{
    $activity = Activity::factory()->for($user)->analyzed()->create();
    $detail = ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => $startDate,
        'distance' => $distanceM,
        'moving_time' => $movingSec ?? max(1, (int) round($distanceM / 1000 * 360)),
    ]);

    return [$activity, $detail];
}

it('returns empty list when start_date_local is missing', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->analyzed()->create();
    $detail = ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => null,
        'distance' => 5000,
        'moving_time' => 1800,
    ]);

    $milestones = app(MilestoneDetector::class)->detect($activity, $detail);

    expect($milestones)->toBe([]);
});

it('fires a first-ever distance milestone when the user crosses a threshold for the first time', function (): void {
    $user = User::factory()->create();
    [$activity, $detail] = buildActivity($user, '2026-05-21', 5_300);

    $milestones = app(MilestoneDetector::class)->detect($activity, $detail);

    $kinds = array_column($milestones, 'kind');
    expect($kinds)->toContain('first_ever_distance');
});

it('does not re-fire first-ever distance once a prior activity hit the same threshold', function (): void {
    $user = User::factory()->create();
    [$prior, $priorDetail] = buildActivity($user, '2026-05-15', 6_000);
    app(MilestoneDetector::class)->detect($prior, $priorDetail);

    [$later, $laterDetail] = buildActivity($user, '2026-05-21', 5_100);
    $milestones = app(MilestoneDetector::class)->detect($later, $laterDetail);

    $kinds = array_column($milestones, 'kind');
    expect($kinds)->not->toContain('first_ever_distance');
});

it('fires longest_ever when a new activity beats the prior longest', function (): void {
    $user = User::factory()->create();
    [$short, $shortDetail] = buildActivity($user, '2026-05-15', 5_000);
    app(MilestoneDetector::class)->detect($short, $shortDetail);

    [$long, $longDetail] = buildActivity($user, '2026-05-21', 8_000);
    $milestones = app(MilestoneDetector::class)->detect($long, $longDetail);

    $kinds = array_column($milestones, 'kind');
    expect($kinds)->toContain('longest_ever');
});

it('includes a PR milestone when categories are passed in', function (): void {
    $user = User::factory()->create();
    [$activity, $detail] = buildActivity($user, '2026-05-21', 5_000);

    $milestones = app(MilestoneDetector::class)->detect($activity, $detail, ['5km']);

    $kinds = array_column($milestones, 'kind');
    expect($kinds)->toContain('pr');
});

it('sorts milestones with PR first (highest priority)', function (): void {
    $user = User::factory()->create();
    [$activity, $detail] = buildActivity($user, '2026-05-21', 10_500, 2_700); // ~4:17 pace → sub-5

    $milestones = app(MilestoneDetector::class)->detect($activity, $detail, ['10km']);

    expect($milestones[0]['kind'])->toBe('pr');
});

it('is idempotent — re-running the detector on the same activity returns the cached payload without re-querying', function (): void {
    $user = User::factory()->create();
    [$activity, $detail] = buildActivity($user, '2026-05-21', 5_500);

    $first = app(MilestoneDetector::class)->detect($activity, $detail);
    $activity->refresh();
    $original = $activity->milestones_detected_at;

    // Sleep imitates a re-sync minutes later; the detected_at timestamp should stay frozen.
    Carbon::setTestNow(Carbon::now()->addMinutes(5));
    $second = app(MilestoneDetector::class)->detect($activity, $detail);
    $activity->refresh();

    // JSON round-trip re-orders associative array keys, so compare canonicalised.
    expect(array_column($second, 'kind'))->toBe(array_column($first, 'kind'))
        ->and($activity->milestones_detected_at?->toIso8601String())->toBe($original?->toIso8601String());

    Carbon::setTestNow();
});

it('treats older activities synced later as not setting a new "first ever" for younger rows', function (): void {
    $user = User::factory()->create();
    // The "new" activity dated 2026-05-21, detected first.
    [$activity, $detail] = buildActivity($user, '2026-05-21', 5_100);
    app(MilestoneDetector::class)->detect($activity, $detail);

    // Now an older backfilled run dated 2026-01-01 arrives — it really WAS the first ever crossing.
    [$older, $olderDetail] = buildActivity($user, '2026-01-01', 5_300);
    $milestones = app(MilestoneDetector::class)->detect($older, $olderDetail);

    $kinds = array_column($milestones, 'kind');
    expect($kinds)->toContain('first_ever_distance');
});
