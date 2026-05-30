<?php

declare(strict_types=1);

namespace App\Jobs\Strava;

use App\Models\Activity;
use App\Services\Run\Ingest\ActivityPipeline;
use App\Services\Strava\Exceptions\StravaRateLimitedException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class IngestActivityJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Exponential backoff (seconds) applied when Strava hands back a 429.
     * A rate-limited release does not count against $tries, so the bucket
     * has time to drain before we try again.
     *
     * @var array<int, int>
     */
    private const array RATE_LIMIT_BACKOFF = [60, 300, 900];

    public function __construct(public readonly int $activityId)
    {
    }

    public function handle(ActivityPipeline $pipeline): void
    {
        $activity = Activity::query()
            ->with('user.stravaConnection')
            ->find($this->activityId);
        if ($activity === null) {
            return;
        }

        try {
            $pipeline->ingest($activity);
        } catch (StravaRateLimitedException $e) {
            $delay = self::RATE_LIMIT_BACKOFF[$this->attempts() - 1]
                ?? self::RATE_LIMIT_BACKOFF[array_key_last(self::RATE_LIMIT_BACKOFF)];

            Log::info('ingest rate-limited; re-queueing with backoff', [
                'activity_id' => $this->activityId,
                'attempt' => $this->attempts(),
                'delay' => $delay,
            ]);

            $this->release($delay);
        }
    }
}
