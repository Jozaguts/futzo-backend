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
use TournamentFormatId;

class TournamentTableSeeder extends Seeder
{
    private const FALLBACK_PHASE = 'Tabla general';
    private const FORMAT_PHASES = [
        TournamentFormatId::League->value => ['Tabla general'],
        TournamentFormatId::LeagueAndElimination->value => ['Tabla general', 'Dieciseisavos de Final', 'Octavos de Final', 'Cuartos de Final', 'Semifinales', 'Final'],
        TournamentFormatId::GroupAndElimination->value => ['Fase de grupos', 'Dieciseisavos de Final', 'Octavos de Final', 'Cuartos de Final', 'Semifinales', 'Final'],
        TournamentFormatId::Elimination->value => ['Dieciseisavos de Final', 'Octavos de Final', 'Cuartos de Final', 'Semifinales', 'Final'],
        TournamentFormatId::Swiss->value => ['Tabla general'],
    ];

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

            $phaseNames = collect(self::FORMAT_PHASES[$tournament->tournament_format_id] ?? [self::FALLBACK_PHASE]);
            $phases = Phase::whereIn('name', $phaseNames)->get()->keyBy('name');
            $phaseNames->each(function (string $phaseName, int $index) use ($phases, $tournament) {
                $phase = $phases->get($phaseName);
                if (!$phase) {
                    return;
                }

                $tournament->tournamentPhases()->firstOrCreate(
                    ['phase_id' => $phase->id],
                    [
                        'is_active' => $index === 0,
                        'is_completed' => false,
                    ]
                );
            });
        }

    }
}
