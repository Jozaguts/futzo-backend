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
        Schema::create('game_general_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->foreign('game_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->string('local_color',10)->nullable();
            $table->string('away_color',10)->nullable();
            $table->unsignedBigInteger('local_captain_id');
            $table->foreign('local_captain_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('away_captain_id');
            $table->foreign('away_captain_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('referee_id');
            $table->foreign('referee_id')
                ->references('id')
                ->on('users');

            $table->unsignedBigInteger('first_assistance_referee_id')->nullable();
            $table->foreign('first_assistance_referee_id')
                ->references('id')
                ->on('users');
            $table->unsignedBigInteger('second_referee_id')->nullable();
            $table->foreign('second_referee_id')
                ->references('id')
                ->on('users');
            $table->unsignedBigInteger('third_referee_id')->nullable();
            $table->foreign('third_referee_id')
                ->references('id')
                ->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_general_details');
    }
};
