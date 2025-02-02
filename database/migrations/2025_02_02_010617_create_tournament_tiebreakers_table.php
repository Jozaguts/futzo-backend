<?php

use App\Models\TournamentConfiguration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_tiebreakers', function (Blueprint $table) {
            $table->id();
            $table->string('rule');
            $table->integer('priority');
            $table->boolean('is_active')->default(true);
            $table->foreignIdFor(TournamentConfiguration::class)->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_tiebreakers');
    }
};
