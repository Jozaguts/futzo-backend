<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->boolean('decided_by_penalties')
                ->default(false)
                ->after('winner_team_id');
            $table->unsignedTinyInteger('penalty_home_goals')
                ->nullable()
                ->after('decided_by_penalties');
            $table->unsignedTinyInteger('penalty_away_goals')
                ->nullable()
                ->after('penalty_home_goals');
            $table->foreignId('penalty_winner_team_id')
                ->nullable()
                ->after('penalty_away_goals')
                ->constrained('teams')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['penalty_winner_team_id']);
            $table->dropColumn([
                'decided_by_penalties',
                'penalty_home_goals',
                'penalty_away_goals',
                'penalty_winner_team_id',
            ]);
        });
    }
};
