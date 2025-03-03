<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('location_tournament', static function (Blueprint $table) {
            if (Schema::hasColumn('location_tournament', 'availability')) {
                $table->dropColumn('availability');
            }
        });
    }
};
