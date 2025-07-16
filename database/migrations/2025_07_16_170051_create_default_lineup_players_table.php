<?php

use App\Models\DefaultLineup;
use App\Models\Player;
use App\Models\Position;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('default_lineup_players', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DefaultLineup::class)->constrained('default_lineups');
            $table->foreignIdFor(Player::class)->constrained('players');
            $table->foreignIdFor(Position::class)->constrained('positions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_lineup_players');
    }
};
