<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schedule_regeneration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')
                ->constrained('leagues')
                ->cascadeOnDelete();
            $table->foreignId('tournament_id')
                ->constrained('tournaments')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('mode', 20);
            $table->unsignedInteger('cutoff_round')->nullable();
            $table->unsignedInteger('completed_rounds')->default(0);
            $table->unsignedInteger('matches_created')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_regeneration_logs');
    }
};
