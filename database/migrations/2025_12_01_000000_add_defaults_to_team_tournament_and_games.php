<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('team_tournament', function (Blueprint $table) {
            $table->foreignId('home_location_id')
                ->nullable()
                ->after('tournament_id')
                ->constrained('locations')
                ->nullOnDelete();

            $table->foreignId('home_field_id')
                ->nullable()
                ->after('home_location_id')
                ->constrained('fields')
                ->nullOnDelete();

            $table->unsignedTinyInteger('home_day_of_week')
                ->nullable()
                ->after('home_field_id')
                ->comment('0=Sunday ... 6=Saturday');

            $table->time('home_start_time')
                ->nullable()
                ->after('home_day_of_week');
        });

        Schema::table('games', function (Blueprint $table) {
            if (Schema::hasColumn('games', 'location_id')) {
                $table->dropForeign(['location_id']);
            }

            $table->string('slot_status', 20)
                ->default('pending')
                ->after('status');

            $table->date('match_date')->nullable()->change();
            $table->time('match_time')->nullable()->change();

            $table->unsignedBigInteger('location_id')->nullable()->change();
            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('slot_status');

            $table->date('match_date')->nullable(false)->change();
            $table->time('match_time')->nullable(false)->change();

            $table->unsignedBigInteger('location_id')->nullable(false)->change();
            $table->foreign('location_id')
                ->references('id')
                ->on('locations');
        });

        Schema::table('team_tournament', function (Blueprint $table) {
            $table->dropForeign(['home_location_id']);
            $table->dropForeign(['home_field_id']);

            $table->dropColumn([
                'home_location_id',
                'home_field_id',
                'home_day_of_week',
                'home_start_time',
            ]);
        });
    }
};
