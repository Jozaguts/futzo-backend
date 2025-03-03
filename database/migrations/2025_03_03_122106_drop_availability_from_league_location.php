<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('league_location', function (Blueprint $table) {
            if (Schema::hasColumn('league_location', 'availability')) {
                $table->dropColumn('availability');
            }
        });
    }
};
