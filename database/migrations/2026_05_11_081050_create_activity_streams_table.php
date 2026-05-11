<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('activity_streams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_streams');
    }
};
