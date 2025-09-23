<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('group_key', 8)->nullable()->after('tournament_phase_id');
            $table->index('group_key');
        });

        DB::statement(<<<SQL
            UPDATE games
            INNER JOIN team_tournament AS tt_home
                ON tt_home.team_id = games.home_team_id
                AND tt_home.tournament_id = games.tournament_id
            INNER JOIN team_tournament AS tt_away
                ON tt_away.team_id = games.away_team_id
                AND tt_away.tournament_id = games.tournament_id
            SET games.group_key = tt_home.group_key
            WHERE tt_home.group_key IS NOT NULL
              AND tt_home.group_key = tt_away.group_key
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['group_key']);
            $table->dropColumn('group_key');
        });
    }
};
