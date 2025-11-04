<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'address')) {
                $table->dropColumn('address');
            }

            if (!Schema::hasColumn('teams', 'home_location_id')) {
                $table->foreignId('home_location_id')
                    ->nullable()
                    ->after('colors')
                    ->constrained('locations')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('teams', 'home_day_of_week')) {
                $table->unsignedTinyInteger('home_day_of_week')
                    ->nullable()
                    ->after('home_location_id')
                    ->comment('0=Sunday ... 6=Saturday');
            }

            if (!Schema::hasColumn('teams', 'home_start_time')) {
                $table->time('home_start_time')
                    ->nullable()
                    ->after('home_day_of_week');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'home_location_id')) {
                $table->dropConstrainedForeignId('home_location_id');
            }

            if (Schema::hasColumn('teams', 'home_day_of_week')) {
                $table->dropColumn('home_day_of_week');
            }

            if (Schema::hasColumn('teams', 'home_start_time')) {
                $table->dropColumn('home_start_time');
            }

            if (!Schema::hasColumn('teams', 'address')) {
                $table->json('address')->nullable()->after('colors');
            }
        });
    }
};
