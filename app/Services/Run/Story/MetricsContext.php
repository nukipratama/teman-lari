<?php

declare(strict_types=1);

namespace App\Services\Run\Story;

use App\Models\User;
use Illuminate\Support\Carbon;

final readonly class MetricsContext
{
    /**
     * @param  array<string, mixed>  $load
     * @param  list<VerdictTimelineItem>  $recentVerdicts
     */
    public function __construct(
        public User $user,
        public string $vibeState,
        public array $load,
        public array $recentVerdicts,
        public Carbon $asOf,
    ) {
    }

}
