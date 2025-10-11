<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SyncQrConfigurationsCommand extends Command
{
    protected $signature = 'qr:sync-configurations';

    protected $description = 'Sincroniza las configuraciones de QR para todas las ligas existentes según los tipos disponibles.';

    public function handle()
    {
        $leagues = DB::table('leagues')->pluck('id');
        $qrTypes = DB::table('qr_types')->get();

        if ($leagues->isEmpty() || $qrTypes->isEmpty()) {
            $this->warn('No hay ligas o tipos de QR registrados.');
            return CommandAlias::SUCCESS;
        }

        $totalCreated = 0;

        foreach ($leagues as $leagueId) {
            foreach ($qrTypes as $type) {
                $exists = DB::table('qr_configurations')
                    ->where('league_id', $leagueId)
                    ->where('qr_type_id', $type->id)
                    ->exists();

                if (!$exists) {
                    DB::table('qr_configurations')->insert([
                        'league_id' => $leagueId,
                        'qr_type_id' => $type->id,
                        'title' => $type->name,
                        'subtitle' => 'Configuración inicial',
                        'description' => $type->description,
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
                    $totalCreated++;
                }
            }
        }

        if ($totalCreated > 0) {
            $this->info("✅ Se crearon {$totalCreated} configuraciones de QR nuevas.");
        } else {
            $this->info("✅ Todas las ligas ya tienen configuraciones de QR actualizadas.");
        }

        return CommandAlias::SUCCESS;
    }
}
