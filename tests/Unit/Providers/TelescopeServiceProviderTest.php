<?php

declare(strict_types=1);

use App\Models\User;
use App\Providers\TelescopeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

uses(RefreshDatabase::class);

it('skips request masking when the app is running locally', function (): void {
    $this->app['env'] = 'local';

    $provider = new TelescopeServiceProvider($this->app);
    $method = new ReflectionMethod($provider, 'hideSensitiveRequestDetails');

    expect(fn () => $method->invoke($provider))->not->toThrow(Throwable::class);
});

it('keeps non-flagged Telescope entries when not running locally', function (): void {
    $entry = Mockery::mock(IncomingEntry::class);
    $entry->shouldReceive('isReportableException')->andReturn(false);
    $entry->shouldReceive('isFailedRequest')->andReturn(false);
    $entry->shouldReceive('isFailedJob')->andReturn(false);
    $entry->shouldReceive('isScheduledTask')->andReturn(false);
    $entry->shouldReceive('hasMonitoredTag')->andReturn(false);

    $filter = end(Telescope::$filterUsing);

    expect($filter($entry))->toBeFalse();
});

it('viewTelescope grants admins whose Strava id is in the allowlist', function (): void {
    $user = User::factory()->withStravaConnection()->create();
    config(['devtools.admin_strava_ids' => [(int) $user->stravaConnection->strava_athlete_id]]);

    expect(Gate::forUser($user->fresh())->allows('viewTelescope'))->toBeTrue();
});

it('viewTelescope denies users whose Strava id is not in the allowlist', function (): void {
    $user = User::factory()->withStravaConnection()->create();
    config(['devtools.admin_strava_ids' => [99_999_999]]);

    expect(Gate::forUser($user->fresh())->allows('viewTelescope'))->toBeFalse();
});
