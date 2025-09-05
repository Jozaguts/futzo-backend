<?php

use App\Models\Field;
use App\Models\League;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('league_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(League::class);
            $table->foreignIdFor(Field::class);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
