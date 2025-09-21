<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $groupAndElimination = 3;

        DB::table('default_tournament_configurations')
            ->where('tournament_format_id', $groupAndElimination)
            ->update(['max_teams' => 36]);

        DB::table('tournament_configurations')
            ->where('tournament_format_id', $groupAndElimination)
            ->update(['max_teams' => 36]);
    }

    public function down(): void
    {
        $groupAndElimination = 3;
        $traditionalFootball = 1;
        $sevenFootball = 2;
        $futsal = 3;

        DB::table('default_tournament_configurations')
            ->where('tournament_format_id', $groupAndElimination)
            ->where('football_type_id', $traditionalFootball)
            ->update(['max_teams' => 32]);

        DB::table('default_tournament_configurations')
            ->where('tournament_format_id', $groupAndElimination)
            ->whereIn('football_type_id', [
                $sevenFootball,
                $futsal,
            ])
            ->update(['max_teams' => 16]);

        DB::table('tournament_configurations')
            ->where('tournament_format_id', $groupAndElimination)
            ->where('football_type_id', $traditionalFootball)
            ->update(['max_teams' => 32]);

        DB::table('tournament_configurations')
            ->where('tournament_format_id', $groupAndElimination)
            ->whereIn('football_type_id', [
                $sevenFootball,
                $futsal,
            ])
            ->update(['max_teams' => 16]);
    }
};
