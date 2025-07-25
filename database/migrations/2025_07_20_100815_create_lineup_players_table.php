<?php

use App\Models\Lineup;
use App\Models\Player;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lineup_players', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Lineup::class)->constrained('lineups');
            $table->foreignIdFor(Player::class)->nullable()->constrained('players');
            $table->unsignedTinyInteger('field_location')->nullable();
            $table->boolean('substituted')->default(false);
            $table->boolean('is_headline')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineup_players');
    }
};
