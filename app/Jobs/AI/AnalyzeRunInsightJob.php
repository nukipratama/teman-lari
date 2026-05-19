<?php

declare(strict_types=1);

namespace App\Jobs\AI;

use App\Exceptions\AI\UnavailableException;
use App\Models\AI\Analysis;
use App\Services\AI\AnalysisType;
use App\Services\AI\Narrators\RunInsightNarrator;
use Override;

class AnalyzeRunInsightJob extends AnalyzeAbstractJob
{
    #[Override]
    protected function generateContent(Analysis $row): string
    {
        [$activity, $detail] = $this->loadAnalyzedActivity($row);
        $narrator = app(RunInsightNarrator::class);

        /** @var array{technical: string, splits: string, zones: string} $payload */
        $payload = cache()->remember(
            "run-insight-llm:{$activity->id}",
            now()->addMinutes(5),
            fn (): array => $narrator->generate($activity, $detail),
        );

        return match ($row->analysis_type) {
            AnalysisType::RunInsightTechnical => $payload['technical'],
            AnalysisType::RunInsightSplits => $payload['splits'],
            AnalysisType::RunInsightZones => $payload['zones'],
            default => throw new UnavailableException("Unsupported analysis_type for run insight: {$row->analysis_type->value}"),
        };
    }
}
