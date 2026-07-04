<?php

declare(strict_types=1);

use App\Models\TelegramConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('unlinks an existing Telegram connection with --force', function (): void {
    $user = User::factory()->create();
    $connection = TelegramConnection::factory()->for($user)->create();

    $this->artisan('telegram:unlink', ['id' => $user->id, '--force' => true])
        ->assertSuccessful();

    expect(TelegramConnection::query()->whereKey($connection->id)->exists())->toBeFalse();
});

it('errors when the user does not exist', function (): void {
    $this->artisan('telegram:unlink', ['id' => 999999, '--force' => true])
        ->assertFailed();
});

it('reports nothing to do when the user has no Telegram connection', function (): void {
    $user = User::factory()->create();

    $this->artisan('telegram:unlink', ['id' => $user->id, '--force' => true])
        ->expectsOutputToContain('has no linked Telegram connection')
        ->assertSuccessful();
});

it('fails loudly instead of silently aborting when run non-interactively without --force', function (): void {
    $user = User::factory()->create();
    $connection = TelegramConnection::factory()->for($user)->create();

    $this->artisan('telegram:unlink', ['id' => $user->id, '--no-interaction' => true])
        ->expectsOutputToContain('No interactive terminal to confirm on.')
        ->assertFailed();

    expect(TelegramConnection::query()->whereKey($connection->id)->exists())->toBeTrue();
});

it('unlinks after an interactive confirmation', function (): void {
    $user = User::factory()->create();
    $connection = TelegramConnection::factory()->for($user)->create();

    $this->artisan('telegram:unlink', ['id' => $user->id])
        ->expectsConfirmation("Unlink Telegram for user {$user->id}?", 'yes')
        ->assertSuccessful();

    expect(TelegramConnection::query()->whereKey($connection->id)->exists())->toBeFalse();
});

it('aborts and unlinks nothing when the confirmation is declined', function (): void {
    $user = User::factory()->create();
    $connection = TelegramConnection::factory()->for($user)->create();

    $this->artisan('telegram:unlink', ['id' => $user->id])
        ->expectsConfirmation("Unlink Telegram for user {$user->id}?", 'no')
        ->assertSuccessful();

    expect(TelegramConnection::query()->whereKey($connection->id)->exists())->toBeTrue();
});
