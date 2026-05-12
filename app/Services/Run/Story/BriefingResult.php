<?php

declare(strict_types=1);

namespace App\Services\Run\Story;

/**
 * Render-ready DTO for the dashboard hero card. All copy is pre-resolved
 * to Indonesian strings; the Blade view does layout only.
 */
final readonly class BriefingResult
{
    public function __construct(
        public string $vibeState,
        public string $vibeLabel,
        public string $vibeEmoji,
        public string $headlineLine,
        public string $suggestionLine,
        public string $recoveryLabel,
        public string $recoveryTone,
        public ?string $streakLabel,
        public string $sigilPattern,
        public ?string $accessory,
        public string $mood,
    ) {
    }
}
