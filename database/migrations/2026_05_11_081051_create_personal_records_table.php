<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('personal_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Distance: 1km, 5km, 10km, 15km, half_marathon, marathon
            // Effort:   best_5min, best_10min, best_20min, best_30min, best_60min
            $table->string('category', 30);
            // Seconds. For distance PRs = elapsed_time at the distance.
            // For effort PRs = pace seconds per km at that duration.
            $table->double('value_sec');
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('set_at');
            $table->timestamps();

            $table->unique(['user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_records');
    }
};
