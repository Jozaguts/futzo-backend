<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_group_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tournament_id');
            $table->unsignedInteger('teams_per_group');
            $table->unsignedInteger('advance_top_n');
            $table->boolean('include_best_thirds')->default(false);
            $table->unsignedInteger('best_thirds_count')->nullable();
            $table->timestamps();

            $table->foreign('tournament_id')
                ->references('id')->on('tournaments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_group_configurations');
    }
};

