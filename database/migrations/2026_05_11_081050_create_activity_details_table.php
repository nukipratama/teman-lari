<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('activity_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->unique()->constrained()->cascadeOnDelete();

            // Strava-sourced facts
            $table->string('name')->nullable();
            $table->dateTime('start_date_local')->nullable();
            $table->double('distance')->nullable();
            $table->unsignedInteger('moving_time')->nullable();
            $table->unsignedInteger('elapsed_time')->nullable();
            $table->double('average_speed')->nullable();
            $table->double('total_elevation_gain')->nullable();
            $table->boolean('has_heartrate')->default(false);
            $table->double('average_heartrate')->nullable();
            $table->unsignedSmallInteger('max_heartrate')->nullable();
            $table->double('average_cadence')->nullable();
            $table->double('calories')->nullable();
            $table->json('splits_metric')->nullable();
            $table->text('summary_polyline')->nullable();

            // Computed
            $table->double('trimp_edwards')->nullable();
            $table->json('stream_summary')->nullable();

            // Weather (no heat flag in v1 — saturated signal in tropical climate)
            $table->smallInteger('weather_temp_c')->nullable();
            $table->unsignedSmallInteger('weather_humidity_pct')->nullable();
            $table->boolean('weather_rain_detected')->nullable();

            // Cached for fast dashboard render
            $table->string('vibe_state', 20)->nullable();

            $table->timestamps();

            $table->index('start_date_local');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_details');
    }
};
