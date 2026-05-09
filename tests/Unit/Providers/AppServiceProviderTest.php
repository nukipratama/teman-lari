<?php

declare(strict_types=1);

use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Strava\Provider as StravaProvider;

it('registers the Strava socialite provider via the SocialiteWasCalled listener', function (): void {
    config([
        'services.strava.client_id' => 'test-id',
        'services.strava.client_secret' => 'test-secret',
        'services.strava.redirect' => 'http://localhost/auth/strava/callback',
    ]);

    expect(Socialite::driver('strava'))->toBeInstanceOf(StravaProvider::class);
});
