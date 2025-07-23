<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournament_configurations', function (Blueprint $table) {
            $table->mediumInteger('substitutions_per_team')->default(3)->after('max_teams')->comment('Indicates how many player substitutions  can be made per game.');
        });
        Schema::table('default_tournament_configurations', function (Blueprint $table) {
            $table->mediumInteger('substitutions_per_team')->default(3)->after('max_teams')->comment('Indicates how many player substitutions  can be made per game.');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_configurations', function (Blueprint $table) {
            $table->dropColumn('substitutions_per_team');
        });
        Schema::table('default_tournament_configurations', function (Blueprint $table) {
            $table->dropColumn('substitutions_per_team');
        });
    }
};
