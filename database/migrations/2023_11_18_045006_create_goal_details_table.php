<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('goal_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->unsignedBigInteger('team_id');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->unsignedBigInteger('player_id');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->dateTime('goal_time');
            $table->enum('goal_type', ['normal', 'own', 'penalty']); // Incluye penal en goal_details
            $table->enum('play_stage', ['group', 'round', 'quarter', 'semi', 'final']);
            $table->enum('goal_schedule', ['normal', 'stoppage', 'extra_time']);
            $table->enum('goal_half', ['time_1', 'time_2',]);
            $table->unsignedBigInteger('goalkeeper_id')->nullable(); // ID del portero en caso de penal
            $table->boolean('penalty_saved')->nullable(); // Indicador si el portero atajó el penal
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
