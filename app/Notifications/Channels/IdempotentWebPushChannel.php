<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use Throwable;
use App\Services\Notifications\NotificationDeliveryClaim;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Wraps the package {@see WebPushChannel} with the shared per-(analysis, channel)
 * delivery claim, so a queued retry — or a fresh notify() for the same analysis
 * (a "Baca ulang" re-analysis, ai:self-heal) — never double-pushes. Notifications
 * that expose no int `deliveryKey()` (streak / test) send without a claim.
 *
 * On a hard send failure the claim is released so the notification's retry can
 * genuinely resend rather than being deduped against its own half-done attempt.
 */
class IdempotentWebPushChannel
{
    private const string CHANNEL = 'webpush';

    public function __construct(
        private readonly WebPushChannel $channel,
        private readonly NotificationDeliveryClaim $claim,
    ) {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        $rawKey = method_exists($notification, 'deliveryKey') ? $notification->deliveryKey() : null;
        $deliveryKey = is_int($rawKey) ? $rawKey : null;

        if ($deliveryKey !== null && ! $this->claim->claim($deliveryKey, self::CHANNEL)) {
            return;
        }

        try {
            $this->channel->send($notifiable, $notification);
        } catch (Throwable $e) {
            if ($deliveryKey !== null) {
                $this->claim->release($deliveryKey, self::CHANNEL);
            }

            throw $e;
        }
    }
}
