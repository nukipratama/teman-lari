<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('story_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // post_run | daily_greeting
            $table->string('kind', 20);
            $table->foreignId('activity_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('for_date')->nullable();
            $table->string('mood', 20);
            $table->text('speech');
            $table->string('sigil_pattern', 40);
            $table->timestamps();

            // post_run rows have non-null activity_id; daily_greeting rows have non-null for_date.
            // MySQL treats NULLs as distinct in unique indexes, so each constraint only fires
            // when both columns are populated — exactly the dedupe we want per kind.
            $table->unique(['user_id', 'activity_id'], 'story_lines_user_activity_unique');
            $table->unique(['user_id', 'for_date'], 'story_lines_user_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_lines');
    }
};
