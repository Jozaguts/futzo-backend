<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\League;
use App\Models\Location;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Models\TournamentFormat;
use Illuminate\Database\Seeder;

class TournamentTableSeeder extends Seeder
{
    private const FORMAT_WITHOUT_PHASES = 'Torneo de Liga';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $league = League::firstOrFail();
        $category = Category::firstOrFail();
        $locations = Location::pluck('id')->all();
        $defaults = config('constants.default_tournament_configuration');
        $tiebreakersConfig = config('constants.tiebreakers');
        $phasesConfig = config('constants.phases');

        foreach ($defaults as $config) {
            // Crear torneo con formato y tipo de fútbol según configuración predeterminada
            $tournament = Tournament::factory()->create([
                'league_id' => $league->id,
                'category_id' => $category->id,
                'tournament_format_id' => $config['tournament_format_id'],
                'football_type_id' => $config['football_type_id'],
            ]);

            // Vincular todas las ubicaciones existentes
            $tournament->locations()->sync($locations);

            // Crear configuración detallada del torneo
            $tournament->configuration()->create([
                'tournament_format_id' => $config['tournament_format_id'],
                'football_type_id' => $config['football_type_id'],
                'game_time' => $config['game_time'] ?? null,
                'time_between_games' => $config['time_between_games'] ?? null,
                'max_teams' => $config['max_teams'],
                'min_teams' => $config['min_teams'],
                'round_trip' => $config['round_trip'],
                'group_stage' => $config['group_stage'],
                'max_players_per_team' => $config['max_players_per_team'],
                'min_players_per_team' => $config['min_players_per_team'],
                'max_teams_per_player' => $config['max_teams_per_player'],
                'elimination_round_trip' => $config['elimination_round_trip'],
            ]);
            // 4) Insertar tiebreakers
            foreach ($tiebreakersConfig as $tb) {
                $tb['tournament_configuration_id'] = $tournament->configuration->id;
                $tournament->configuration->tiebreakers()->create($tb);
            }

            // 5) Insertar fases
            // obtener nombre del formato para comparar
            $formatName = $tournament->format->name;
            if ($formatName === self::FORMAT_WITHOUT_PHASES) {
                // Solo fase “Tabla general”
                $tournament->phases()->create(
                    collect($phasesConfig)->firstWhere('name', 'Tabla general')
                );
            } else {
                // Todas menos la fase “Tabla general”
                collect($phasesConfig)
                    ->reject(fn($p) => $p['name'] === 'Tabla general')
                    ->each(fn($p) => $tournament->phases()->create($p));
            }
        }
    }
}
