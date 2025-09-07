<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_tournament', function (Blueprint $table) {
            $table->string('group_key', 8)->nullable()->after('tournament_id');
        });
    }

    public function down(): void
    {
        Schema::table('team_tournament', function (Blueprint $table) {
            $table->dropColumn('group_key');
        });
    }
};

