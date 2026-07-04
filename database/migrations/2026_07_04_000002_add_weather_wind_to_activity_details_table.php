<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('activity_details', function (Blueprint $table): void {
            $table->unsignedSmallInteger('weather_wind_speed_kmh')->nullable()->after('weather_rain_detected');
            $table->unsignedSmallInteger('weather_wind_gust_kmh')->nullable()->after('weather_wind_speed_kmh');
            // Bearing the wind comes FROM, 0-359 degrees.
            $table->unsignedSmallInteger('weather_wind_direction_deg')->nullable()->after('weather_wind_gust_kmh');
            // true = rain flag came from the forecast endpoint (uncertain); false = observed/archive.
            $table->boolean('weather_rain_is_forecast')->nullable()->after('weather_wind_direction_deg');
        });
    }

    public function down(): void
    {
        Schema::table('activity_details', function (Blueprint $table): void {
            $table->dropColumn([
                'weather_wind_speed_kmh',
                'weather_wind_gust_kmh',
                'weather_wind_direction_deg',
                'weather_rain_is_forecast',
            ]);
        });
    }
};
