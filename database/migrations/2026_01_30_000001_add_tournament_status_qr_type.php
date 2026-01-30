<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $type = [
            'name' => 'Resultados del torneo',
            'key' => 'tournament_status',
            'description' => 'Codigo QR que dirige a la vista publica de resultados del torneo.',
        ];

        $typeId = DB::table('qr_types')
            ->where('key', $type['key'])
            ->value('id');

        if (!$typeId) {
            $typeId = DB::table('qr_types')->insertGetId([
                'name' => $type['name'],
                'key' => $type['key'],
                'description' => $type['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $leagueIds = DB::table('leagues')->pluck('id');
        foreach ($leagueIds as $leagueId) {
            $exists = DB::table('qr_configurations')
                ->where('league_id', $leagueId)
                ->where('qr_type_id', $typeId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('qr_configurations')->insert([
                'league_id' => $leagueId,
                'qr_type_id' => $typeId,
                'title' => $type['name'],
                'subtitle' => 'Configuracion inicial',
                'description' => $type['description'],
                'background_color' => '#F4F5FA',
                'foreground_color' => '#2E263D',
                'primary_color' => '#7E4EE6',
                'font_family' => 'Inter_24pt-Regular.ttf',
                'font_size_title' => 48,
                'font_size_subtitle' => 32,
                'font_size_description' => 24,
                'logo_path' => 'images/vertical/logo-09.png',
                'layout' => 'square',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $typeId = DB::table('qr_types')
            ->where('key', 'tournament_status')
            ->value('id');

        if (!$typeId) {
            return;
        }

        DB::table('qr_configurations')
            ->where('qr_type_id', $typeId)
            ->delete();

        DB::table('qr_types')
            ->where('id', $typeId)
            ->delete();
    }
};
