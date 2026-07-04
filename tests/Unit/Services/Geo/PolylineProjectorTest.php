<?php

declare(strict_types=1);

use App\Services\Geo\PolylineDecoder;
use App\Services\Geo\PolylineProjector;

function project(?string $polyline, float $w = 320, float $h = 320, float $pad = 24): ?string
{
    return (new PolylineProjector(new PolylineDecoder()))->project($polyline, $w, $h, $pad);
}

it('returns null when there is no polyline', function (): void {
    expect(project(null))->toBeNull()
        ->and(project(''))->toBeNull();
});

it('returns null when the polyline decodes to fewer than two points', function (): void {
    // A single [lat,lng] pair encodes to one point — not drawable.
    expect(project('_p~iF~ps|U'))->toBeNull();
});

it('projects points fitted inside the padded box', function (): void {
    $points = project('_p~iF~ps|U_ulLnnqC_mqNvxq`@', 320, 320, 24);

    expect($points)->not->toBeNull();

    $coords = array_map(
        fn (string $pair): array => array_map('floatval', explode(',', $pair)),
        explode(' ', $points),
    );

    foreach ($coords as [$x, $y]) {
        expect($x)->toBeGreaterThanOrEqual(24.0)->toBeLessThanOrEqual(296.0)
            ->and($y)->toBeGreaterThanOrEqual(24.0)->toBeLessThanOrEqual(296.0);
    }
});

it('honours a non-square box', function (): void {
    $points = project('_p~iF~ps|U_ulLnnqC_mqNvxq`@', 484, 330, 34);

    $xs = array_map(fn (string $pair): float => (float) explode(',', $pair)[0], explode(' ', $points));

    expect(max($xs))->toBeLessThanOrEqual(450.0); // width(484) - pad(34)
});
