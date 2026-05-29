<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

/**
 * A start point for a demo run: real Indonesian coordinates plus a
 * pre-resolved location name so seeding skips the Nominatim lookup and the
 * location chip renders immediately.
 */
final readonly class DemoLocation
{
    public function __construct(
        public float $lat,
        public float $lng,
        public string $name,
        public string $country = 'ID',
    ) {
    }

    /**
     * Curated running spots across Indonesia. Coordinates are real; names
     * follow the Nominatim shape "<spot>, <city>, <province>, Indonesia".
     *
     * The order is referenced by position in BlueprintLibrary (append-only —
     * reordering reshuffles which scripted run happens where). The synthesis
     * hot loop resolves a run's location once, so this isn't rebuilt per point.
     *
     * @return list<DemoLocation>
     */
    public static function library(): array
    {
        return [
            new DemoLocation(-6.2186, 106.8021, 'Gelora Bung Karno, Jakarta Pusat, DKI Jakarta, Indonesia'),
            new DemoLocation(-6.1754, 106.8272, 'Monas, Jakarta Pusat, DKI Jakarta, Indonesia'),
            new DemoLocation(-6.1095, 106.7400, 'Pantai Indah Kapuk, Jakarta Utara, DKI Jakarta, Indonesia'),
            new DemoLocation(-6.8995, 107.6130, 'Taman Saparua, Bandung, Jawa Barat, Indonesia'),
            new DemoLocation(-7.2920, 112.7400, 'Taman Bungkul, Surabaya, Jawa Timur, Indonesia'),
            new DemoLocation(-7.8120, 110.3600, 'Alun-alun Kidul, Yogyakarta, DI Yogyakarta, Indonesia'),
            new DemoLocation(-8.6905, 115.2620, 'Pantai Sanur, Denpasar, Bali, Indonesia'),
            new DemoLocation(-6.9930, 110.4220, 'Simpang Lima, Semarang, Jawa Tengah, Indonesia'),
        ];
    }

    /**
     * The fallback start point for any GPS run that didn't name its own
     * location (Gelora Bung Karno, Jakarta). Named so callers don't depend on
     * a magic list index.
     */
    public static function default(): DemoLocation
    {
        return self::library()[0];
    }
}
