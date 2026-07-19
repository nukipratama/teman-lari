<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

it('renders the Cloudflare beacon when a token is configured', function (): void {
    Config::set('services.cloudflare.web_analytics_token', 'test-token-123');

    $this->get('/login')
        ->assertOk()
        ->assertSee('static.cloudflareinsights.com/beacon.min.js', escape: false)
        ->assertSee('test-token-123', escape: false);
});

it('omits the beacon when no token is configured', function (): void {
    Config::set('services.cloudflare.web_analytics_token', null);

    $this->get('/login')
        ->assertOk()
        ->assertDontSee('cloudflareinsights.com', escape: false);
});
