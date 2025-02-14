<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('default_tournament_configurations', static function (Blueprint $table) {
            $table->boolean('elimination_round_trip')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('default_tournament_configurations', static function (Blueprint $table) {
            $table->dropColumn('elimination_round_trip');
        });
    }
};
