<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('match_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tournament_id');
            $table->unsignedBigInteger('home_team_id');
            $table->unsignedBigInteger('away_team_id');
            $table->unsignedBigInteger('field_id')->nullable();
            $table->unsignedInteger('round');
            $table->date('match_date');
            $table->time('match_time');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('referee_id')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'canceled']);
            $table->string('result')->nullable();


            $table->foreign('field_id')->references('id')->on('fields')->onDelete('set null');
            $table->foreign('tournament_id')->references('id')->on('tournaments');
            $table->foreign('home_team_id')->references('id')->on('teams');
            $table->foreign('away_team_id')->references('id')->on('teams');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('referee_id')->references('id')->on('referees');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_schedules');
    }
};
