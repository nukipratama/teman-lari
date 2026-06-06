<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Exceptions\AI\UnavailableException;
use App\Models\AI\Analysis;
use App\Models\User;
use App\Services\AI\RuleBased\RuleBasedInsightBuilder;
use Override;

class AnalyzeTrendCaptionJob extends AnalyzeRowJob
{
    #[Override]
    protected function generateContent(Analysis $row): string
    {
        $user = User::query()->find($row->subject_id);
        if ($user === null) {
            throw new UnavailableException("User {$row->subject_id} not found");
        }

        return app(RuleBasedInsightBuilder::class)->trendCaption($user, $this->discriminatorDate($row));
    }
}
