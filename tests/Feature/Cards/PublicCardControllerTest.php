<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\ActivityDetail;
use App\Models\RunCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

function signedCardUrl(RunCard $card): string
{
    return URL::signedRoute('kartu.publik', ['card' => $card->id]);
}

it('renders the public card page for a valid signature', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create([
        'distance' => 5_280,
        'summary_polyline' => '_p~iF~ps|U_ulLnnqC_mqNvxq`@',
    ]);
    $card = RunCard::factory()->for($activity)->create([
        'rarity' => 'epic',
        'special_move' => 'Tendangan Balik',
    ]);

    $response = $this->get(signedCardUrl($card));

    $response->assertSuccessful();
    $response->assertSee('Tendangan Balik', escape: false);
    $response->assertSee('Istimewa', escape: false); // rarity label
    $response->assertSee('<meta property="og:title" content="Tendangan Balik">', escape: false);
    $response->assertSee('twitter:card', escape: false);
    $response->assertSee('<polyline', escape: false);
});

it('is reachable without authentication', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create();
    $card = RunCard::factory()->for($activity)->create();

    $this->get(signedCardUrl($card))->assertSuccessful();
});

it('rejects an invalid signature with 403', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create();
    $card = RunCard::factory()->for($activity)->create();

    $this->get(signedCardUrl($card).'tampered')->assertForbidden();
});

it('rejects a missing signature with 403', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create();
    $card = RunCard::factory()->for($activity)->create();

    $this->get("/k/{$card->id}")->assertForbidden();
});

it('points the OG image at the dynamic card image route', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create();
    $card = RunCard::factory()->for($activity)->create();

    $response = $this->get(signedCardUrl($card));

    $response->assertSee(route('kartu.image', $card), escape: false);
    $response->assertDontSee('/og-card.png', escape: false);
});

it('serves the card image as a PNG without a signature', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->for($user)->analyzed()->create();
    ActivityDetail::factory()->for($activity)->create([
        'distance' => 5_280,
        'summary_polyline' => '_p~iF~ps|U_ulLnnqC_mqNvxq`@',
    ]);
    $card = RunCard::factory()->for($activity)->create();

    $response = $this->get(route('kartu.image', $card));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'image/png');
    expect(str_starts_with($response->getContent(), "\x89PNG\r\n\x1a\n"))->toBeTrue();
});

it('404s the card image for a missing card', function (): void {
    $this->get('/k/999999/image.png')->assertNotFound();
});
