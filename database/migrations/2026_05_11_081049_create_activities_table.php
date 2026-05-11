<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('strava_external_id');
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->unsignedTinyInteger('detail_fail_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'strava_external_id']);
            $table->index('analyzed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
