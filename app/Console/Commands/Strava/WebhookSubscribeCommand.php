<?php

declare(strict_types=1);

namespace App\Console\Commands\Strava;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Manage the Strava push subscription (one per application).
 *
 * @see https://developers.strava.com/docs/webhooks/
 */
#[Signature('strava:webhook-subscribe
    {--action=view : create | view | delete}
    {--id= : Subscription id to delete (required for --action=delete)}')]
#[Description('Create, view, or delete the Strava webhook push subscription.')]
class WebhookSubscribeCommand extends Command
{
    private const string SUBSCRIPTIONS_URL = 'https://www.strava.com/api/v3/push_subscriptions';

    public function handle(): int
    {
        $clientId = config('services.strava.client_id');
        $clientSecret = config('services.strava.client_secret');

        if (blank($clientId) || blank($clientSecret)) {
            $this->error('Strava client_id / client_secret are not configured.');

            return self::FAILURE;
        }

        return match ($this->option('action')) {
            'create' => $this->create((string) $clientId, (string) $clientSecret),
            'view' => $this->view((string) $clientId, (string) $clientSecret),
            'delete' => $this->delete((string) $clientId, (string) $clientSecret),
            default => $this->invalidAction(),
        };
    }

    private function create(string $clientId, string $clientSecret): int
    {
        $verifyToken = config('services.strava.webhook_verify_token');
        if (blank($verifyToken)) {
            $this->error('STRAVA_WEBHOOK_VERIFY_TOKEN is not configured.');

            return self::FAILURE;
        }

        $callbackUrl = route('strava.webhook.verify');

        $response = Http::asForm()->post(self::SUBSCRIPTIONS_URL, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'callback_url' => $callbackUrl,
            'verify_token' => $verifyToken,
        ]);

        if ($response->failed()) {
            $this->error("Strava rejected the subscription ({$response->status()}): {$response->body()}");

            return self::FAILURE;
        }

        $id = $response->json('id');
        $this->info("Subscription created with id {$id}.");
        $this->line("Callback URL: {$callbackUrl}");
        $this->line('Set STRAVA_WEBHOOK_SUBSCRIPTION_ID in your env to record it.');

        return self::SUCCESS;
    }

    private function view(string $clientId, string $clientSecret): int
    {
        $response = Http::get(self::SUBSCRIPTIONS_URL, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if ($response->failed()) {
            $this->error("Could not fetch subscriptions ({$response->status()}): {$response->body()}");

            return self::FAILURE;
        }

        $subscriptions = $response->json();
        if (! is_array($subscriptions) || $subscriptions === []) {
            $this->warn('No active push subscription.');

            return self::SUCCESS;
        }

        foreach ($subscriptions as $subscription) {
            $id = $subscription['id'] ?? '?';
            $callback = $subscription['callback_url'] ?? '?';
            $this->line("id={$id}  callback={$callback}");
        }

        return self::SUCCESS;
    }

    private function delete(string $clientId, string $clientSecret): int
    {
        $id = $this->option('id') ?? config('services.strava.webhook_subscription_id');
        if (blank($id)) {
            $this->error('Pass --id=<subscription id> (or set STRAVA_WEBHOOK_SUBSCRIPTION_ID).');

            return self::FAILURE;
        }

        $response = Http::asForm()->delete(self::SUBSCRIPTIONS_URL.'/'.$id, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if ($response->failed()) {
            $this->error("Could not delete subscription {$id} ({$response->status()}): {$response->body()}");

            return self::FAILURE;
        }

        $this->info("Subscription {$id} deleted.");

        return self::SUCCESS;
    }

    private function invalidAction(): int
    {
        $this->error('Unknown --action. Use one of: create, view, delete.');

        return self::FAILURE;
    }
}
