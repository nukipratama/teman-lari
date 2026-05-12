<?php

declare(strict_types=1);

namespace App\Services\Run\Story;

use Illuminate\Support\Carbon;

/**
 * One row in the dashboard "Kata Temari" strip. Pre-rendered so the Blade
 * view does layout only.
 */
final readonly class VerdictTimelineItem
{
    public function __construct(
        public int $activityId,
        public string $mood,
        public string $moodFace,
        public string $oneline,
        public Carbon $startedAt,
        public float $distanceKm,
    ) {
    }
}
