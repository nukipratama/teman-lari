<?php

declare(strict_types=1);

namespace App\Services\Run\Story\Narrators;

use App\Models\User;
use App\Services\Run\Story\BriefingResult;
use App\Services\Run\Story\Contracts\BriefingNarrator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class FallbackBriefingNarrator implements BriefingNarrator
{
    public function __construct(
        private BriefingNarrator $primary,
        private BriefingNarrator $secondary,
    ) {
    }

    public function generate(User $user, ?Carbon $asOf = null): BriefingResult
    {
        try {
            return $this->primary->generate($user, $asOf);
        } catch (Throwable $e) {
            Log::warning('narrator.briefing.fail', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);
            $fallback = $this->secondary->generate($user, $asOf);
            $fallback->degraded = true;

            return $fallback;
        }
    }
}
