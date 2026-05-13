<?php

declare(strict_types=1);

namespace App\Services\Geo;

// https://developers.google.com/maps/documentation/utilities/polylinealgorithm
class PolylineEncoder
{
    /**
     * @param  array<int, array{0: float, 1: float}>  $points
     */
    public function encode(array $points): string
    {
        $out = '';
        $prevLat = 0;
        $prevLng = 0;
        foreach ($points as [$lat, $lng]) {
            $iLat = (int) round($lat * 1e5);
            $iLng = (int) round($lng * 1e5);
            $out .= $this->encodeSigned($iLat - $prevLat);
            $out .= $this->encodeSigned($iLng - $prevLng);
            $prevLat = $iLat;
            $prevLng = $iLng;
        }

        return $out;
    }

    private function encodeSigned(int $value): string
    {
        // ZigZag: positives stay even, negatives become odd magnitudes.
        $shifted = ($value < 0) ? ~($value << 1) : ($value << 1);

        return $this->encodeUnsigned($shifted);
    }

    private function encodeUnsigned(int $value): string
    {
        $out = '';
        while ($value >= 0x20) {
            $out .= chr((0x20 | ($value & 0x1F)) + 63);
            $value >>= 5;
        }
        $out .= chr($value + 63);

        return $out;
    }
}
