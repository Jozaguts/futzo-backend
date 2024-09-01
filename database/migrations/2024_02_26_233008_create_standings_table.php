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
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('team_tournament_id');
            $table->foreign('team_tournament_id')
                ->references('id')
                ->on('team_tournament')
                ->cascadeOnDelete();
            // played games
            $table->integer('pg')->default(0);
            // won games
            $table->integer('w')->default(0);
            // drawn games
            $table->integer('d')->default(0);
            // lost games
            $table->integer('l')->default(0);
            // goals in favor
            $table->integer('gf')->default(0);
            // goals against
            $table->integer('ga')->default(0);
            // goal difference
            $table->integer('gd')->default(0);
            // points
            $table->integer('pts')->default(0);
            // las five games
            $table->string('last_5')->default('-----');
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
