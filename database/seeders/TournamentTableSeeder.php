<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\League;
use App\Models\Location;
use App\Models\Phase;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use App\Models\FootballType;
use App\Models\DefaultTournamentConfiguration;
use Illuminate\Database\Seeder;

class TournamentTableSeeder extends Seeder
{
    private const FORMAT_WITHOUT_PHASES = 'Torneo de Liga';

    public function run(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $league = League::firstOrFail();
            $category = Category::firstOrFail();

            // Tomamos la configuración “por defecto” previamente sembrada
            $defaultConfig = DefaultTournamentConfiguration::firstOrFail();

            // Relacionados belongsTo
            $format = TournamentFormat::findOrFail($defaultConfig->tournament_format_id);
            $fType = FootballType::findOrFail($defaultConfig->football_type_id);

            // Creamos el torneo vinculando las relaciones belongsTo
            $tournament = Tournament::factory()
                ->for($league)                         // league_id
                ->for($category)                       // category_id
                ->for($format, 'format')               // tournament_format_id
                ->for($fType, 'footballType')          // football_type_id
                ->create();

            // Vinculamos ubicaciones al pivot location_tournament
            $locationIds = Location::pluck('id');
            $tournament->locations()->sync($locationIds);

            // Creamos la configuración detallada con los mismos valores
            $tournament->configuration()->create([
                'tournament_format_id' => $defaultConfig->tournament_format_id,
                'football_type_id' => $defaultConfig->football_type_id,
                'game_time' => $defaultConfig->game_time,
                'substitutions_per_team' => $defaultConfig->substitutions_per_team,
                'time_between_games' => $defaultConfig->time_between_games,
                'max_teams' => $defaultConfig->max_teams,
                'min_teams' => $defaultConfig->min_teams,
                'round_trip' => $defaultConfig->round_trip,
                'group_stage' => $defaultConfig->group_stage,
                'max_players_per_team' => $defaultConfig->max_players_per_team,
                'min_players_per_team' => $defaultConfig->min_players_per_team,
                'max_teams_per_player' => $defaultConfig->max_teams_per_player,
                'elimination_round_trip' => $defaultConfig->elimination_round_trip,
            ]);

            // Insertamos los tiebreakers
            collect(config('constants.tiebreakers'))->each(function ($tb) use ($tournament) {
                $tournament->configuration
                    ->tiebreakers()
                    ->create(array_merge($tb, [
                        'tournament_configuration_id' => $tournament->configuration->id,
                    ]));
            });

            // Insertamos fases según el formato
            $allPhases = Phase::all();
            if ($tournament->format->name === self::FORMAT_WITHOUT_PHASES) {
                // Solo "Tabla general"
                $phase = $allPhases->where('name', 'Tabla general')->first();
                $tournament->tournamentPhases()->create(
                    [
                        'phase_id' => $phase->id,
                        'is_active' => true,
                        'is_completed' => false,
                    ]
                );
            } else {
                // Todas menos "Tabla general"
                $allPhases
                    ->reject(fn($p) => $p['name'] === 'Tabla general')
                    ->each(fn($p) => $tournament->tournamentPhases()->create(
                        [
                            'phase_id' => $p->id,
                            'is_active' => false,
                            'is_completed' => false,
                        ]
                    ));
            }
        }

    }
}
