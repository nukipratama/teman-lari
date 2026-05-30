<?php

declare(strict_types=1);

namespace App\Console\Commands\Strava;

use App\Models\User;
use App\Services\Run\Ingest\SyncOrchestrator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('strava:sync
    {--user= : Sync only this user id; otherwise all connected users}
    {--since= : Only consider activities started after this date (e.g. 2026-05-01 or "-7 days"); bounds the backfill walk}')]
#[Description('Fetch new Strava activities and queue them for ingestion.')]
class SyncCommand extends Command
{
    public function handle(SyncOrchestrator $orchestrator): int
    {
        $users = $this->resolveUsers();
        if ($users->isEmpty()) {
            $this->warn('No users with a Strava connection found.');

            return self::SUCCESS;
        }

        $since = $this->resolveSince();

        foreach ($users as $user) {
            $queued = $orchestrator->syncUser($user, $since);
            $this->line("user {$user->id}: {$queued} new activities queued");
        }

        return self::SUCCESS;
    }

    private function resolveSince(): ?CarbonImmutable
    {
        $since = $this->option('since');
        if (! is_string($since) || $since === '') {
            return null;
        }

        return CarbonImmutable::parse($since);
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveUsers(): Collection
    {
        return User::query()
            ->whereHas('stravaConnection', fn ($query) => $query->whereNull('revoked_at'))
            ->with('stravaConnection')
            ->when($this->option('user'), fn ($query, $userId) => $query->whereKey($userId))
            ->get();
    }
}
