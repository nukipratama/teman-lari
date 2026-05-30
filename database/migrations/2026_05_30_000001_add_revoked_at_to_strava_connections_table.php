<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * `revoked_at` flips once Strava rejects a token refresh or the athlete
     * deauthorizes the app. A revoked connection is skipped by sync instead
     * of looping retries until $tries is exhausted.
     */
    public function up(): void
    {
        Schema::table('strava_connections', function (Blueprint $table): void {
            $table->timestamp('revoked_at')->nullable()->after('scopes');
        });
    }

    public function down(): void
    {
        Schema::table('strava_connections', function (Blueprint $table): void {
            $table->dropColumn('revoked_at');
        });
    }
};
