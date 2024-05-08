<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournament_configuration', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tournament_id');
            $table->foreign('tournament_id')->references('id')->on('tournaments')->onDelete('cascade');
            $table->unsignedBigInteger('tournament_type_id');
            $table->foreign('tournament_type_id')->references('id')->on('tournament_types')->onDelete('cascade');
            $table->integer('max_participants')->nullable();
            $table->integer('min_participants')->nullable();
            $table->integer('max_teams')->nullable();
            $table->integer('min_teams')->nullable();
            $table->integer('max_players_per_team')->nullable();
            $table->integer('min_players_per_team')->nullable();
            $table->integer('max_teams_per_player')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_configuration');
    }
};
