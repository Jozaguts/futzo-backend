<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key');
            $table->string('description');
            $table->timestamps();
        });
        $initialTypes = [
            [
                'name' => 'Registro de equipos',
                'key' => 'team_registration',
                'description' => 'Código QR para que los equipos se inscriban en un torneo.',
            ],
            [
                'name' => 'Registro de jugadores',
                'key' => 'player_registration',
                'description' => 'Código QR para que los jugadores se registren en un equipo.',
            ],
            [
                'name' => 'Información de liga',
                'key' => 'league_info',
                'description' => 'Código QR que dirige al perfil o información general de la liga.',
            ],
            [
                'name' => 'Información de torneo',
                'key' => 'tournament_info',
                'description' => 'Código QR que dirige a la página o calendario del torneo.',
            ],
            [
                'name' => 'Detalles del partido',
                'key' => 'game_details',
                'description' => 'Código QR con la información de un partido específico (equipos, hora, campo).',
            ],
            [
                'name' => 'Calendario de juego',
                'key' => 'schedule_access',
                'description' => 'Código QR que muestra el calendario completo de una liga o torneo.',
            ],
        ];

        foreach ($initialTypes as $type) {
            if (!DB::table('qr_types')->where('key', $type['key'])->exists()) {
                DB::table('qr_types')->insert($type);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_types');
    }
};
