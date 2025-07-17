<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('4-4-2');
            $table->integer('goalkeeper')->default(1);
            $table->integer('defenses')->default(4);
            $table->integer('midfielders')->default(4);
            $table->integer('forwards')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};
