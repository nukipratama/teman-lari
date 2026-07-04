<?php

declare(strict_types=1);

namespace App\Services\Weather;

final readonly class WeatherSnapshot
{
    public function __construct(
        public int $tempC,
        public int $humidityPct,
        public bool $rainDetected,
        public ?int $windSpeedKmh = null,
        public ?int $windGustKmh = null,
        public ?int $windDirectionDeg = null,
        public bool $rainIsForecast = false,
    ) {
    }
}
