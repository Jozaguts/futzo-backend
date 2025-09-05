<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_field_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_field_id')->constrained('league_fields')->cascadeOnDelete();
            $table->tinyInteger('day_of_week');
            $table->unsignedSmallInteger('start_minute');
            $table->unsignedSmallInteger('end_minute');
            $table->boolean('exclusive')->default(true);
            // Opcionalmente acotar al rango del torneo; se pueden usar más adelante
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->index(['league_field_id', 'day_of_week'], 'tfr_league_field_day_idx');
            $table->index(['tournament_id', 'day_of_week'], 'tfr_tournament_day_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_field_reservations');
    }
};

