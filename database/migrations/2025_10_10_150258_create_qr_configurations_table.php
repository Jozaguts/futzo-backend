<?php

use App\Models\League;
use App\Models\QrType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(League::class)->constrained('leagues');
            $table->foreignIdFor(QrType::class)->constrained('qr_types');
            $table->string('title');
            $table->string('subtitle');
            $table->text('description');

            // Futzo (basado en theme light)
            $table->string('background_color', 10)->default('#F4F5FA');
            $table->string('foreground_color', 10)->default('#2E263D');
            $table->string('primary_color', 10)->default('#7E4EE6');

            // Tipografía y estilos
            $table->string('font_family')->default('Inter_24pt-Regular.ttf');
            $table->integer('font_size_title')->default(48);
            $table->integer('font_size_subtitle')->default(32);
            $table->integer('font_size_description')->default(24);


            $table->string('logo_path')->nullable();
            $table->enum('layout',['square','portrait','landscape'])->default('square');
            $table->unique(['league_id', 'qr_type_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_configurations');
    }
};
