<?php

declare(strict_types=1);

namespace App\Services\Geo;

/**
 * Projects an encoded summary polyline into an SVG `points` string fitted to a
 * padded box (north up), preserving aspect ratio and centring the route in the
 * box. Shared by the public card page (square glyph) and the server-rendered
 * share/OG image (landscape panel), mirroring the client RouteGlyph projection.
 */
class PolylineProjector
{
    public function __construct(private readonly PolylineDecoder $decoder)
    {
    }

    /**
     * The fitted `points` string, or null when there's nothing drawable (no
     * polyline, or fewer than two decoded points). Coordinates are relative to
     * the box origin (0,0), so the caller can translate them into place.
     */
    public function project(?string $polyline, float $width, float $height, float $pad): ?string
    {
        if ($polyline === null || $polyline === '') {
            return null;
        }

        $points = $this->decoder->decode($polyline);
        if (\count($points) < 2) {
            return null;
        }

        $lats = array_column($points, 0);
        $lngs = array_column($points, 1);
        $minLat = min($lats);
        $maxLat = max($lats);
        $minLng = min($lngs);
        $maxLng = max($lngs);
        $spanLat = ($maxLat - $minLat) ?: 1;
        $spanLng = ($maxLng - $minLng) ?: 1;

        $innerW = $width - $pad * 2;
        $innerH = $height - $pad * 2;
        $scale = min($innerW / $spanLng, $innerH / $spanLat);
        $offX = $pad + ($innerW - $spanLng * $scale) / 2;
        $offY = $pad + ($innerH - $spanLat * $scale) / 2;

        $coords = array_map(
            fn (array $p): string => sprintf(
                '%.1f,%.1f',
                $offX + ($p[1] - $minLng) * $scale,
                $offY + ($maxLat - $p[0]) * $scale, // flip y: higher latitude = higher on screen
            ),
            $points,
        );

        return implode(' ', $coords);
    }
}
