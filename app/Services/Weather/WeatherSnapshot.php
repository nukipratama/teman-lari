<?php

declare(strict_types=1);

namespace App\Services\Weather;

final readonly class WeatherSnapshot
{
    public function __construct(
        public int $tempC,
        public int $humidityPct,
        public bool $rainDetected,
    ) {
    }
}
