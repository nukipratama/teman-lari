<?php

declare(strict_types=1);

namespace App\Services\Run\Ingest;

use App\Jobs\Strava\IngestActivityJob;
use App\Models\Activity;
use App\Models\User;
use App\Services\Strava\ActivityFetcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncOrchestrator
{
    private const int LOCK_TTL_SECONDS = 300;

    public function __construct(private readonly ActivityFetcher $fetcher)
    {
    }

    public function syncUser(User $user): int
    {
        $connection = $user->stravaConnection;
        if ($connection === null) {
            return 0;
        }

        $lock = Cache::lock("strava-sync:user-{$user->id}", self::LOCK_TTL_SECONDS);
        if (! $lock->get()) {
            Log::info('strava-sync skipped — another run holds the lock', ['user_id' => $user->id]);

            return 0;
        }

        try {
            $newIds = $this->fetcher->fetchNewExternalIds($connection);
            if ($newIds === []) {
                return 0;
            }

            $inserted = $this->insertActivityRows($user->id, $newIds);

            foreach (Activity::query()
                ->where('user_id', $user->id)
                ->whereIn('strava_external_id', $newIds)
                ->orderBy('id')
                ->lazy() as $activity
            ) {
                IngestActivityJob::dispatch($activity->id);
            }

            Log::info('strava-sync queued ingestion', [
                'user_id' => $user->id,
                'inserted' => $inserted,
            ]);

            return $inserted;
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  list<int>  $externalIds  already sorted ascending (oldest first)
     */
    private function insertActivityRows(int $userId, array $externalIds): int
    {
        $now = now();
        $rows = array_map(fn (int $id): array => [
            'user_id' => $userId,
            'strava_external_id' => $id,
            'fetched_at' => $now,
            'analyzed_at' => null,
            'detail_fail_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $externalIds);

        return DB::transaction(
            fn (): int => (int) Activity::query()->insertOrIgnore($rows)
        );
    }
}
