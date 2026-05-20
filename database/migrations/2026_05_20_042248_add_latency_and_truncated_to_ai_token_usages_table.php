<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('ai_token_usages', function (Blueprint $table): void {
            // Wall time of the Azure round trip. Nullable for the failure path
            // where the call threw before we got a measurement.
            $table->unsignedInteger('latency_ms')->nullable()->after('total_tokens');

            // Azure returned finish_reason=length — output got cut off. Signal
            // for "raise max_completion_tokens or shorten the prompt."
            $table->boolean('truncated')->default(false)->after('latency_ms');
        });
    }

    public function down(): void
    {
        Schema::table('ai_token_usages', function (Blueprint $table): void {
            $table->dropColumn(['latency_ms', 'truncated']);
        });
    }
};
