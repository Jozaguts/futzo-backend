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
        Schema::create('tournament_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('tournament_format_id')->constrained()->onDelete('cascade');
            $table->foreignId('football_type_id')->constrained()->onDelete('cascade');
            $table->integer('game_time')->nullable();
            $table->integer('time_between_games')->nullable();
            $table->integer('max_teams')->nullable();
            $table->integer('min_teams')->nullable();
            $table->boolean('round_trip')->default(false);
            $table->boolean('group_stage')->default(false);
            $table->integer('max_players_per_team')->nullable();
            $table->integer('min_players_per_team')->nullable();
            $table->integer('max_teams_per_player')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_configurations');
    }
};
