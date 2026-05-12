<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\ActivityDetail;
use App\Models\StoryLine;
use App\Models\User;
use App\Services\Run\Story\Temari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow('2026-05-11 12:00:00'));
afterEach(fn () => Carbon::setTestNow());

function seedDashboardVerdict(User $user, int $daysAgo, string $mood, string $speech): Activity
{
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => Carbon::today()->subDays($daysAgo),
        'distance' => 5000.0 + ($daysAgo * 100),
        'trimp_edwards' => 60.0,
    ]);
    StoryLine::query()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
        'kind' => StoryLine::KIND_POST_RUN,
        'mood' => $mood,
        'speech' => $speech,
        'sigil_pattern' => 'dddd',
    ]);

    return $activity;
}

it('shows "Kata Temari" strip when the user has post-run verdicts', function (): void {
    $user = User::factory()->create();
    seedDashboardVerdict($user, 0, Temari::MOOD_BOUNCY, 'Run yang mantap');

    $this->actingAs($user)->get('/dashboard')
        ->assertSuccessful()
        ->assertSeeText('Kata Temari')
        ->assertSeeText('Run yang mantap');
});

it('omits the strip when there are no post-run StoryLines', function (): void {
    $user = User::factory()->create();
    // An activity but no StoryLine row.
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create([
        'start_date_local' => Carbon::today(),
        'trimp_edwards' => 60.0,
    ]);

    $this->actingAs($user)->get('/dashboard')
        ->assertSuccessful()
        ->assertDontSeeText('Kata Temari');
});

it('renders verdicts newest-first and links each to the run detail', function (): void {
    $user = User::factory()->create();
    $newest = seedDashboardVerdict($user, 0, Temari::MOOD_BOUNCY, 'verdict newest');
    $older = seedDashboardVerdict($user, 2, Temari::MOOD_DIM, 'verdict older');

    $response = $this->actingAs($user)->get('/dashboard')->assertSuccessful();

    // Both links present.
    $response->assertSeeText('verdict newest')
        ->assertSeeText('verdict older')
        ->assertSee(route('runs.show', $newest->id), false)
        ->assertSee(route('runs.show', $older->id), false);

    // Newest must appear before older in the rendered HTML.
    $html = $response->getContent();
    expect(strpos($html, 'verdict newest'))->toBeLessThan(strpos($html, 'verdict older'));
});

it('does not leak verdicts across users', function (): void {
    $a = User::factory()->create();
    $b = User::factory()->create();
    seedDashboardVerdict($a, 0, Temari::MOOD_BOUNCY, 'a-only line');

    $this->actingAs($b)->get('/dashboard')
        ->assertSuccessful()
        ->assertDontSeeText('a-only line');
});
