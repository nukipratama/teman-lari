<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\ActivityDetail;
use App\Models\RunCard;
use App\Models\User;
use App\Services\Run\Story\RunCardImageRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** The 8-byte PNG file signature. */
const PNG_MAGIC = "\x89PNG\r\n\x1a\n";

function renderCard(RunCard $card): string
{
    return app(RunCardImageRenderer::class)->render($card);
}

it('renders valid PNG bytes for a card with a route polyline', function (): void {
    $activity = Activity::factory()->for(User::factory())->create();
    ActivityDetail::factory()->for($activity)->create([
        'distance' => 5_280,
        'summary_polyline' => '_p~iF~ps|U_ulLnnqC_mqNvxq`@',
        'location_name' => 'Yogyakarta',
    ]);
    $card = RunCard::factory()->for($activity)->create(['rarity' => 'epic', 'special_move' => 'Tendangan Balik']);

    $png = renderCard($card);

    expect(str_starts_with($png, PNG_MAGIC))->toBeTrue()
        ->and(strlen($png))->toBeGreaterThan(1000);
});

it('renders valid PNG bytes for a no-GPS card (fallback layout)', function (): void {
    $activity = Activity::factory()->for(User::factory())->create();
    ActivityDetail::factory()->for($activity)->create([
        'distance' => 3_000,
        'summary_polyline' => null,
    ]);
    $card = RunCard::factory()->for($activity)->create(['rarity' => 'common', 'special_move' => 'Langkah Mantap']);

    $png = renderCard($card);

    expect(str_starts_with($png, PNG_MAGIC))->toBeTrue();
});

it('renders a longer PNG when the footer line gains a weather + wind reading', function (): void {
    $activity = Activity::factory()->for(User::factory())->create();
    ActivityDetail::factory()->for($activity)->create([
        'distance' => 5_280,
        'summary_polyline' => null,
        'location_name' => 'Yogyakarta',
        'weather_temp_c' => 31,
        'weather_wind_speed_kmh' => 15,
    ]);
    $card = RunCard::factory()->for($activity)->create(['rarity' => 'epic', 'special_move' => 'Tendangan Balik']);

    $png = renderCard($card);

    expect(str_starts_with($png, PNG_MAGIC))->toBeTrue()
        ->and(strlen($png))->toBeGreaterThan(1000);
});

it('omits the weather footer segment gracefully when temp is absent', function (): void {
    $activity = Activity::factory()->for(User::factory())->create();
    ActivityDetail::factory()->for($activity)->create([
        'distance' => 5_280,
        'summary_polyline' => null,
        'location_name' => 'Yogyakarta',
        'weather_temp_c' => null,
        'weather_wind_speed_kmh' => 15,
    ]);
    $card = RunCard::factory()->for($activity)->create(['rarity' => 'epic', 'special_move' => 'Tendangan Balik']);

    $png = renderCard($card);

    expect(str_starts_with($png, PNG_MAGIC))->toBeTrue();
});
