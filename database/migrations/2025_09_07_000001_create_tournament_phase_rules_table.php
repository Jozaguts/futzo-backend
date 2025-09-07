<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_phase_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tournament_phase_id');
            $table->boolean('round_trip')->default(false);
            $table->boolean('away_goals')->default(false);
            $table->boolean('extra_time')->default(true);
            $table->boolean('penalties')->default(true);
            $table->enum('advance_if_tie', ['better_seed', 'none'])->default('better_seed');
            $table->timestamps();

            $table->foreign('tournament_phase_id')
                ->references('id')->on('tournament_phases')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_phase_rules');
    }
};

