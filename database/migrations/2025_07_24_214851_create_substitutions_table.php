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
        Schema::create('substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Game::class)->constrained('games');
            $table->foreignIdFor(Team::class)->constrained('teams');
            $table->foreignIdFor(Player::class, 'player_in_id')->constrained('players');
            $table->foreignIdFor(Player::class, 'player_out_id')->constrained('players');
            $table->unsignedTinyInteger('minute');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substitutions');
    }
};
