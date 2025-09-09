<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Formation;
use App\Models\League;
use App\Models\Location;
use App\Models\Phase;
use App\Models\Player;
use App\Models\Position;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Models\TournamentFormat;
use App\Models\TournamentGroupConfiguration;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SeedLeagueTournamentsCommand extends Command
{
    protected $signature = 'futzo:seed-league
        {league_id=1 : ID de la liga}
        {--teams=16 : Número de equipos por torneo}
        {--players-min=16 : Jugadores mínimos por equipo}
        {--players-max=23 : Jugadores máximos por equipo}
        {--football-type-id=1 : Tipo de fútbol (1=F11)}
    ';

    protected $description = 'Genera 3 torneos (Liga, Liga+Eliminatoria, Grupos+Eliminatoria) con equipos y jugadores para una liga dada';

    public function handle(): int
    {
        $leagueId = (int) $this->argument('league_id');
        $teamsPerTournament = (int) $this->option('teams');
        $playersMin = (int) $this->option('players-min');
        $playersMax = (int) $this->option('players-max');
        $footballTypeId = (int) $this->option('football-type-id');

        $league = League::find($leagueId);
        if (!$league) {
            $this->error("Liga {$leagueId} no encontrada");
            return self::FAILURE;
        }

        if ($playersMin < 1 || $playersMax < $playersMin) {
            $this->error('Parámetros inválidos: players-min y players-max');
            return self::FAILURE;
        }

        $category = Category::first();
        if (!$category) {
            $this->error('No existen categorías; ejecuta los seeders base.');
            return self::FAILURE;
        }
        $formation = Formation::first();
        if (!$formation) {
            $this->error('No existen formaciones; ejecuta los seeders base.');
            return self::FAILURE;
        }
        $positions = Position::pluck('id');
        if ($positions->isEmpty()) {
            $this->error('No existen posiciones; ejecuta los seeders base.');
            return self::FAILURE;
        }

        $formats = [
            ['id' => 1, 'name' => 'Torneo de Liga', 'label' => 'Liga'],
            ['id' => 2, 'name' => 'Liga y Eliminatoria', 'label' => 'Liga+Elim'],
            ['id' => 5, 'name' => 'Grupos y Eliminatoria', 'label' => 'Grupos+Elim'],
        ];

        foreach ($formats as $fmt) {
            $this->info("Generando torneo: {$fmt['label']} ({$fmt['name']})");
            $bar = $this->output->createProgressBar($teamsPerTournament);
            $bar->start();

            DB::transaction(function () use (
                $league,
                $category,
                $formation,
                $positions,
                $footballTypeId,
                $teamsPerTournament,
                $playersMin,
                $playersMax,
                $fmt,
                $bar
            ) {
                // 1) Crear torneo
                $format = TournamentFormat::find($fmt['id'])
                    ?: TournamentFormat::where('name', $fmt['name'])->first()
                    ?: TournamentFormat::create([
                        'name' => $fmt['name'],
                        'description' => $fmt['name'],
                        'status' => 'created',
                    ]);
                $name = sprintf('Demo %s %s %s', $league->name, $format->name, uniqid('t', false));
                $tournament = Tournament::create([
                    'name' => $name,
                    'start_date' => now()->addDays(3)->toDateString(),
                    'end_date' => now()->addDays(30)->toDateString(),
                    'status' => 'creado',
                    'category_id' => $category->id,
                    'tournament_format_id' => $format->id,
                    'football_type_id' => $footballTypeId,
                    'league_id' => $league->id,
                ]);

                // 2) Configuración por defecto según formato/tipo
                $def = \App\Models\DefaultTournamentConfiguration::where([
                    'tournament_format_id' => $format->id,
                    'football_type_id' => $footballTypeId,
                ])->first();
                $cfg = [
                    'tournament_format_id' => $format->id,
                    'football_type_id' => $footballTypeId,
                    'game_time' => $def->game_time ?? 90,
                    'time_between_games' => $def->time_between_games ?? 0,
                    'substitutions_per_team' => $def->substitutions_per_team ?? 3,
                    'max_teams' => $def->max_teams ?? $teamsPerTournament,
                    'min_teams' => $def->min_teams ?? min(8, $teamsPerTournament),
                    'round_trip' => (bool) ($def->round_trip ?? ($format->id === 1)),
                    'group_stage' => (bool) ($def->group_stage ?? ($format->id === 5)),
                    'max_players_per_team' => $def->max_players_per_team ?? max($playersMax, 18),
                    'min_players_per_team' => $def->min_players_per_team ?? min($playersMin, 11),
                    'max_teams_per_player' => $def->max_teams_per_player ?? 1,
                    'elimination_round_trip' => (bool) ($def->elimination_round_trip ?? ($format->id !== 1)),
                ];
                $tournament->configuration()->create($cfg);

                // 3) Fases
                $phases = Phase::all();
                if ($format->name === 'Torneo de Liga') {
                    $phase = $phases->firstWhere('name', 'Tabla general');
                    if ($phase) {
                        $tournament->tournamentPhases()->create([
                            'phase_id' => $phase->id,
                            'is_active' => true,
                            'is_completed' => false,
                        ]);
                    }
                } else {
                    $phases->reject(fn($p) => $p->name === 'Tabla general')
                        ->each(function ($p) use ($tournament) {
                            $tournament->tournamentPhases()->create([
                                'phase_id' => $p->id,
                                'is_active' => false,
                                'is_completed' => false,
                            ]);
                        });
                }

                // 4) Config de grupos (si aplica)
                if ($format->id === 5) { // Grupos y Eliminatoria
                    TournamentGroupConfiguration::updateOrCreate(
                        ['tournament_id' => $tournament->id],
                        [
                            'teams_per_group' => 4,
                            'advance_top_n' => 2,
                            'include_best_thirds' => false,
                            'best_thirds_count' => null,
                        ]
                    );
                }

                // 5) Vincular ubicaciones de la liga (si existen)
                $locationIds = $league->locations()->pluck('locations.id');
                if ($locationIds->isNotEmpty()) {
                    $tournament->locations()->sync($locationIds);
                }

                // 6) Equipos + jugadores
                for ($i = 0; $i < $teamsPerTournament; $i++) {
                    // Coach y presidente
                    $coach = User::factory()->create(['league_id' => $league->id]);
                    $president = User::factory()->create(['league_id' => $league->id]);

                    $team = \App\Models\Team::factory()
                        ->state([
                            'coach_id' => $coach->id,
                            'president_id' => $president->id,
                        ])
                        ->create();

                    $team->leagues()->attach($league->id);
                    $team->categories()->attach($category->id);
                    $team->tournaments()->attach($tournament->id);
                    $team->defaultLineup()->create(['formation_id' => $formation->id]);

                    $playersCount = random_int($playersMin, $playersMax);
                    $usedNumbers = [];
                    for ($p = 0; $p < $playersCount; $p++) {
                        $user = User::factory()->create(['league_id' => $league->id]);
                        // Asignar número único por equipo (si es posible)
                        $num = random_int(1, 99);
                        $attempts = 0;
                        while (in_array($num, $usedNumbers, true) && $attempts < 5) {
                            $num = random_int(1, 99);
                            $attempts++;
                        }
                        $usedNumbers[] = $num;
                        Player::create([
                            'user_id' => $user->id,
                            'team_id' => $team->id,
                            'category_id' => $category->id,
                            'position_id' => $positions->random(),
                            'number' => $num,
                            'nationality' => Arr::random(config('constants.nationalities')),
                        ]);
                    }

                    $bar->advance();
                }

                $this->info("Torneo generado: {$tournament->name} ({$format->name}) con {$teamsPerTournament} equipos");
            }, 5);

            $bar->finish();
            $this->newLine();
        }

        $this->info('Listo. Se generaron 3 torneos con equipos y jugadores.');
        return self::SUCCESS;
    }
}
