<?php

use App\Models\Formation;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('default_lineups', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Team::class)->constrained('teams');
            $table->foreignIdFor(Formation::class)->constrained('formations');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_lineups');
    }
};
