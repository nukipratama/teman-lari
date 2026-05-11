<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('weekly_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_ending');
            $table->double('distance_km')->nullable();
            $table->unsignedSmallInteger('runs')->nullable();
            $table->double('weekly_trimp')->nullable();
            $table->double('atl_7d')->nullable();
            $table->double('ctl_42d')->nullable();
            $table->double('form')->nullable();
            $table->string('form_status', 20)->nullable();
            $table->double('avg_decoupling')->nullable();
            $table->double('monotony')->nullable();
            $table->double('strain')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week_ending']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_snapshots');
    }
};
