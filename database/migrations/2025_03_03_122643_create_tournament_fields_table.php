<?php

use App\Models\Field;
use App\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class);
            $table->foreignIdFor(Field::class);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
