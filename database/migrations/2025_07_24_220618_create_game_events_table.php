<?php

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->enum('type',['goal', 'yellow_card', 'red_card', 'assist', 'substitution', 'penalty_kick', 'own_goal', 'foul']);
            $table->unsignedMediumInteger('minute');
            $table->foreignIdFor(Game::class)->constrained('games');
            $table->foreignIdFor(Player::class)->constrained('players');
            $table->foreignIdFor(Player::class, 'related_player_id')->nullable()->constrained('players');
            $table->foreignIdFor(Team::class)->constrained('teams');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_events');
    }
};
