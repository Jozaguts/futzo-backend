<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('league_field_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_field_id')->constrained('league_fields')->cascadeOnDelete();
            $table->tinyInteger('day_of_week');
            $table->unsignedSmallInteger('start_minute');
            $table->unsignedSmallInteger('end_minute');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['league_field_id', 'day_of_week'], 'lfw_league_field_day_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_field_windows');
    }
};

