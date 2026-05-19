<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('viewHorizon grants admins whose Strava id is in the allowlist', function (): void {
    $user = User::factory()->withStravaConnection()->create();
    config(['devtools.admin_strava_ids' => [(int) $user->stravaConnection->strava_athlete_id]]);

    expect(Gate::forUser($user->fresh())->allows('viewHorizon'))->toBeTrue();
});

it('viewHorizon denies users whose Strava id is not in the allowlist', function (): void {
    $user = User::factory()->withStravaConnection()->create();
    config(['devtools.admin_strava_ids' => [99_999_999]]);

    expect(Gate::forUser($user->fresh())->allows('viewHorizon'))->toBeFalse();
});
