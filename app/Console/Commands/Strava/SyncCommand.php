<?php

declare(strict_types=1);

namespace App\Console\Commands\Strava;

use App\Models\User;
use App\Services\Run\Ingest\SyncOrchestrator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('strava:sync {--user= : Sync only this user id; otherwise all connected users}')]
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

        foreach ($users as $user) {
            $queued = $orchestrator->syncUser($user);
            $this->line("user {$user->id}: {$queued} new activities queued");
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveUsers(): Collection
    {
        return User::query()
            ->whereHas('stravaConnection')
            ->with('stravaConnection')
            ->when($this->option('user'), fn ($query, $userId) => $query->whereKey($userId))
            ->get();
    }
}
