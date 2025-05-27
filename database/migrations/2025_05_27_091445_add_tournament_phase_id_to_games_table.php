<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTournamentPhaseIdToGamesTable extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->unsignedBigInteger('tournament_phase_id')->nullable()->after('tournament_id');
            $table->foreign('tournament_phase_id')
                ->references('id')
                ->on('tournament_phases')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            //
        });
    }
}
