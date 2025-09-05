<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('field_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week'); // 0=Domingo .. 6=Sábado
            $table->unsignedSmallInteger('start_minute'); // 0..1440
            $table->unsignedSmallInteger('end_minute');   // exclusivo
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['field_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_windows');
    }
};

