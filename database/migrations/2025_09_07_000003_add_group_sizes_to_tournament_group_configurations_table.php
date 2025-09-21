<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournament_group_configurations', function (Blueprint $table) {
            $table->json('group_sizes')->nullable()->after('teams_per_group');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_group_configurations', function (Blueprint $table) {
            $table->dropColumn('group_sizes');
        });
    }
};
