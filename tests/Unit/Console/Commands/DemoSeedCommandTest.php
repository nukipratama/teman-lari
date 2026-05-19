<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\PersonalRecord;
use App\Models\RunCard;
use App\Models\StoryLine;
use App\Models\User;
use App\Models\WeeklySnapshot;
use Database\Seeders\Demo\DemoRunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// Freeze today so blueprint subDays() anchors and ISO-week math are stable.
beforeEach(fn () => Carbon::setTestNow('2026-05-12 12:00:00'));
afterEach(fn () => Carbon::setTestNow());

it('creates the demo user, runs, cards, story lines, PRs, and weekly snapshots', function (): void {
    $exitCode = $this->artisan('demo:seed', ['--fresh' => true])->run();

    expect($exitCode)->toBe(0);

    $user = User::query()->where('email', DemoRunSeeder::DEMO_USER_EMAIL)->firstOrFail();

    // 16 scripted + RNG fillers @ 65% over ~90d = 63; exact match fails loud on drift.
    $activityCount = Activity::query()->where('user_id', $user->id)->count();
    expect($activityCount)->toBe(63);

    expect(RunCard::query()->whereIn('activity_id', Activity::query()->where('user_id', $user->id)->pluck('id'))->count())
        ->toBe($activityCount)
        ->and(StoryLine::query()->where('user_id', $user->id)->where('kind', StoryLine::KIND_POST_RUN)->count())
        ->toBe($activityCount)
        ->and(StoryLine::query()->where('user_id', $user->id)->where('kind', StoryLine::KIND_DAILY_GREETING)->count())
        ->toBe(1)
        ->and(WeeklySnapshot::query()->where('user_id', $user->id)->count())->toBe(14)
        ->and(PersonalRecord::query()->where('user_id', $user->id)->count())->toBe(8);
});

it('is idempotent — re-running with --fresh produces a consistent row count', function (): void {
    $this->artisan('demo:seed', ['--fresh' => true])->assertSuccessful();
    $firstUser = User::query()->where('email', DemoRunSeeder::DEMO_USER_EMAIL)->firstOrFail();
    $first = Activity::query()->where('user_id', $firstUser->id)->count();

    $this->artisan('demo:seed', ['--fresh' => true])->assertSuccessful();
    $secondUser = User::query()->where('email', DemoRunSeeder::DEMO_USER_EMAIL)->firstOrFail();
    $second = Activity::query()->where('user_id', $secondUser->id)->count();

    expect($second)->toBe($first);
});
