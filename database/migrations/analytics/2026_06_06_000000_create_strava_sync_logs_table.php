<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user Strava sync telemetry on the dedicated `analytics` schema.
 *
 * Tracks sync outcomes, API call usage, and rate-limit headroom so the
 * Pulse Strava Health card can surface per-user breakdowns and detect
 * revoked / rate-limited / errored connections early.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::connection('analytics')->create('strava_sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status', 32); // success, rate_limited, token_expired, revoked, error, deleted
            $table->unsignedInteger('activities_synced')->default(0);
            $table->unsignedInteger('api_calls_used')->default(0);
            $table->unsignedInteger('rate_limit_15min_remaining')->nullable();
            $table->unsignedInteger('rate_limit_daily_remaining')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('synced_at');

            $table->index(['user_id', 'synced_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('analytics')->dropIfExists('strava_sync_logs');
    }
};
