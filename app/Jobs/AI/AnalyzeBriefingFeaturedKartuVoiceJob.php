<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Models\AI\Analysis;
use App\Models\RunCard;
use App\Models\User;
use App\Services\AI\Narrators\BriefingFeaturedKartuVoiceNarrator;

/**
 * Standalone row job for the "Kata Temari" quote on the Featured Kartu panel.
 * Split from {@see AnalyzeBriefingMascotVoiceJob} so retrying one surface
 * doesn't also re-spend LLM tokens on the other.
 */
class AnalyzeBriefingFeaturedKartuVoiceJob extends AnalyzeRowJob
{
    protected function generateContent(Analysis $row): string
    {
        $user = User::query()->findOrFail($row->subject_id);

        // The discriminator is the featured card's id, so the narration always
        // describes the exact card the hero shows; a stale/changed pick keys to a
        // different row instead of stranding old text. A null/non-card id falls
        // through to the "no card yet" line in the narrator.
        $card = is_numeric($row->discriminator)
            ? RunCard::query()->with('activity.detail')->find((int) $row->discriminator)
            : null;

        return app(BriefingFeaturedKartuVoiceNarrator::class)->generate($user, $card);
    }
}
