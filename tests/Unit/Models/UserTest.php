<?php

declare(strict_types=1);

use App\Models\StravaConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('hides remember_token from array serialization', function (): void {
    $user = User::factory()->create();

    expect($user->toArray())->not->toHaveKey('remember_token');
});

it('has at most one strava connection', function (): void {
    $user = User::factory()->withStravaConnection()->create();

    expect($user->stravaConnection)->toBeInstanceOf(StravaConnection::class)
        ->and($user->stravaConnection->user->is($user))->toBeTrue();
});

it('returns null stravaConnection when none is attached', function (): void {
    $user = User::factory()->create();

    expect($user->stravaConnection)->toBeNull();
});
