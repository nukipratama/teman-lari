<?php

declare(strict_types=1);

namespace App\Services\Run\Story;

final class BriefingResult
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
        public bool $degraded = false,
    ) {
    }
}
