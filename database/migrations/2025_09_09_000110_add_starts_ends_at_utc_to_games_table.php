<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->timestamp('starts_at_utc')->nullable()->after('match_time')->index();
            $table->timestamp('ends_at_utc')->nullable()->after('starts_at_utc')->index();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['starts_at_utc', 'ends_at_utc']);
        });
    }
};

