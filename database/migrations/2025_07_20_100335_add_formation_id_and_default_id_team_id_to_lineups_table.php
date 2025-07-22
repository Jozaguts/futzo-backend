<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lineups', function (Blueprint $table) {
            $table->foreignId('formation_id')->after('game_id')->nullable()->constrained();
            $table->foreignId('default_lineup_id')->after('formation_id')->nullable()->constrained();
            $table->foreignId('team_id')->after('default_lineup_id')->nullable()->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('lineups', function (Blueprint $table) {
            $table->dropForeign(['formation_id']);
            $table->dropForeign(['default_lineup_id']);
            $table->dropForeign(['team_id']);
            $table->dropColumn(['formation_id', 'default_lineup_id','team_id']);
        });
    }
};
