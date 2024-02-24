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
        Schema::create('game_time_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->foreign('game_id')
                ->references('id')
                ->on('games')
                ->cascadeOnDelete();
            $table->time('first_time_start')->nullable();
            $table->time('first_time_end')->nullable();
            $table->time('second_time_start')->nullable();
            $table->time('second_time_end')->nullable();
            $table->time('prorogue_minutes_start')->nullable();
            $table->time('first_time_extra_time')->nullable();
            $table->time('second_time_extra_time')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_time_details');
    }
};
