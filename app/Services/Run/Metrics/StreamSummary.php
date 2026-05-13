<?php

declare(strict_types=1);

namespace App\Services\Run\Metrics;

final class StreamSummary
{
    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, float|int>
     */
    public static function zonePct(array $summary): array
    {
        $pct = $summary['time_in_zone_pct'] ?? null;

        return is_array($pct) ? $pct : [];
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public static function hardZoneShare(array $summary): float
    {
        $zonePct = self::zonePct($summary);

        return (float) ($zonePct['Z3'] ?? 0)
            + (float) ($zonePct['Z4'] ?? 0)
            + (float) ($zonePct['Z5'] ?? 0);
    }
}
