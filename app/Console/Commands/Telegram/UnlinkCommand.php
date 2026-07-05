<?php

declare(strict_types=1);

namespace App\Console\Commands\Telegram;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('telegram:unlink {id : The user id to unlink from Telegram} {--force : Skip the confirmation prompt}')]
#[Description('Delete a user\'s Telegram connection, stopping all Telegram pushes for that user.')]
class UnlinkCommand extends Command
{
    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $user = User::query()->find($id);

        if ($user === null) {
            $this->error("User {$id} not found.");

            return self::FAILURE;
        }

        $connection = $user->telegramConnection;

        if ($connection === null) {
            $this->info("User {$id} has no linked Telegram connection, nothing to do.");

            return self::SUCCESS;
        }

        $this->table(['What', 'Value'], [
            ['User', "{$user->name} <{$user->email}> (id {$id})"],
            ['Telegram', 'linked'],
        ]);

        if (! $this->option('force')) {
            // isInteractive() alone only flips false for an explicit --no-interaction
            // flag: a bare `docker exec` (no -it) still reports interactive=true, so
            // confirm() reads immediate EOF as its "no" default and the command exits
            // 0 having silently done nothing. Also require a real stdin TTY (skipped
            // under tests, whose own stdin is never a TTY either) to catch that case.
            $hasRealTerminal = $this->laravel->runningUnitTests()
                || (defined('STDIN') && stream_isatty(STDIN));

            if (! $this->input->isInteractive() || ! $hasRealTerminal) {
                $this->error('No interactive terminal to confirm on. Re-run with a TTY (docker exec -it ...) or pass --force to skip the prompt.');

                return self::FAILURE;
            }

            if (! $this->confirm("Unlink Telegram for user {$id}?")) {
                $this->info('Aborted, nothing unlinked.');

                return self::SUCCESS;
            }
        }

        DB::transaction(function () use ($connection): void {
            $connection->delete();
        });

        $this->info("Unlinked Telegram for user {$id}. Telegram pushes are now stopped for this user.");

        return self::SUCCESS;
    }
}
