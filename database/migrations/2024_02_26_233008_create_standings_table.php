<?php


use App\Models\League;
use App\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('team_tournament_id');
            $table->foreign('team_tournament_id')
                ->references('id')
                ->on('team_tournament')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('updated_after_game_id');
            $table->foreign('updated_after_game_id')
                ->references('id')
                ->on('games')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tournament_phase_id');
            $table->foreign('tournament_phase_id')
                ->references('id')
                ->on('tournament_phases')
                ->cascadeOnDelete();

            $table->foreignIdFor(Tournament::class)->constrained('tournaments');
            $table->foreignIdFor(League::class)->constrained('leagues');
            $table->unique(['team_tournament_id', 'tournament_phase_id'], 'standings_unique_phase');
            // played games
            $table->integer('matches_played')->default(0);
            // won games
            $table->integer('wins')->default(0);
            // drawn games
            $table->integer('draws')->default(0);
            // lost games
            $table->integer('losses')->default(0);
            // goals in favor
            $table->integer('goals_for')->default(0);
            // goals against
            $table->integer('goals_against')->default(0);
            // goal difference
            $table->integer('goal_difference')->default(0);
            // points
            $table->integer('points')->default(0);
            // En algunas ligas, las tarjetas y sanciones afectan la clasificación en desempates.
            $table->integer('fair_play_points')->default(0);
            // las five games
            $table->string('last_5')->default('-----');
            $table->integer('rank')->nullable()->default(null);

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
