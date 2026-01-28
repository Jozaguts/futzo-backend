<?php

namespace App\Http\Controllers;

use App\DTO\TournamentDTO;
use App\Events\TournamentCreatedEvent;
use App\Exports\RoundExport;
use App\Exports\TournamentStandingExport;
use App\Exports\TournamentStatsExport;
use App\Facades\QrTemplateRendererService;
use App\Http\Requests\CreateTournamentScheduleRequest;
use App\Http\Requests\TournamentStoreRequest;
use App\Http\Requests\TournamentUpdateRequest;
use App\Http\Requests\UpdateTournamentRoundRequest;
use App\Http\Requests\UpdateTournamentStatusRequest;
use App\Http\Requests\SetTournamentRoundByeRequest;
use App\Http\Requests\SetTournamentRoundScheduleRequest;
use App\Http\Resources\FieldResource;
use App\Http\Resources\GameResource;
use App\Http\Resources\LastGamesCollection;
use App\Http\Resources\NextGamesCollection;
use App\Http\Resources\ScheduleSettingsResource;
use App\Http\Resources\TournamentCollection;
use App\Http\Resources\TournamentResource;
use App\Http\Resources\TournamentScheduleCollection;
use App\Jobs\RegenerateScheduleWithForcedByeJob;
use App\Jobs\RegenerateScheduleWithFixedRoundJob;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Location;
use App\Models\QrConfiguration;
use App\Models\ScheduleRegenerationLog;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use App\Models\TournamentPhase;
use App\Models\TournamentConfiguration;
use App\Models\TournamentFieldReservation;
use App\Services\ScheduleGeneratorService;
use App\Services\RoundStatusService;
use App\Services\TournamentScheduleRegenerationService;
use Barryvdh\Snappy\Facades\SnappyImage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

use App\Enums\TournamentFormatId;
class TournamentController extends Controller
{
    const string IMG_EXPORT_TYPE = 'img';
    const string XSL_EXPORT_TYPE = 'excel';
    public function index(Request $request): TournamentCollection
    {

        $query = Tournament::withCount(['teams', 'players', 'games'])
            ->with([
                'format' => function ($query) {
                    $query->select('id', 'name');
                },
                'locations'
            ]);

        $allowedStatuses = ['creado', 'en curso', 'completado', 'cancelado'];
        $statusFilters = collect(Arr::wrap($request->input('status')))
            ->flatMap(fn ($value) => explode(',', (string) $value))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->unique()
            ->intersect($allowedStatuses)
            ->values();

        if ($statusFilters->isNotEmpty()) {
            $query->whereIn('status', $statusFilters->all());
        }

        $tournaments = $query->paginate(
            $request->get('per_page', 10),
            ['*'],
            'page',
            $request->get('page', 1)
        );

        return new TournamentCollection($tournaments);
    }

    /**
     * @throws \Throwable
     * @throws \JsonException
     */
    public function store(TournamentStoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $tourneyDto = (new TournamentDTO($request->validated()));
            $tournament = Tournament::create($tourneyDto->basicFields());

            if ($tourneyDto->hasLocation) {
                $tournament->locations()->attach($tourneyDto->locationFields());
            }

            if ($request->hasFile('basic.image')) {
                $media = $tournament
                    ->addMedia($tourneyDto->getImage())
                    ->toMediaCollection('tournament');

                $tournament->update([
                    'image' => $media->getUrl('default'),
                    'thumbnail' => $media->getUrl('thumbnail')
                ]);
            }
            TournamentCreatedEvent::dispatch($tournament, $tourneyDto->basicFields());
            DB::commit();
        } catch (FileIsTooBig|FileDoesNotExist $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return response()->json($tournament, 201);
    }

    public function show($tournament): TournamentResource
    {
        $tournament = Tournament::where('id', $tournament)
            ->orWhere('slug', $tournament)
            ->with(['teams', 'players', 'games'])
            ->firstOrFail();
        return new TournamentResource($tournament);
    }

    public function update(TournamentUpdateRequest $request, Tournament $tournament): TournamentResource
    {
        $data = $request->safe()->collect();
        $location = null;

        if ($data->has('location')) {
            $requestLocation = json_decode($data->get('location'), true);

            $location = Location::updateOrCreate([
                'place_id' => $requestLocation['place_id']
            ], [
                'name' => $requestLocation['structured_formatting']['main_text'],
                'address' => $requestLocation['description'],
            ]);
        }
        $tournament->update($data->except('location')->toArray());

        if ($data->has('image')) {
            $media = $tournament
                ->addMedia($data->get('image'))
                ->toMediaCollection('tournament');

            $tournament->update([
                'image' => $media->getUrl('default'),
                'thumbnail' => $media->getUrl('thumbnail')
            ]);
        }

        if (!is_null($location)) {
            $tournament->locations()->sync([$location->id]);
        }
        return new TournamentResource($tournament);
    }

    public function getTournamentTypes(): JsonResponse
    {
        $tournamentTypes = TournamentFormat::query()->select('id', 'name')->get();

        return response()->json($tournamentTypes);
    }

    public function getTournamentFormats(): JsonResponse
    {
        $tournamentFormats = TournamentFormat::query()
            ->select('id', 'name', 'description')
            ->whereNot('name','Sistema suizo')
            ->get();

        return response()->json($tournamentFormats);
    }

    public function updateStatus(UpdateTournamentStatusRequest $request, Tournament $tournament): JsonResponse
    {

        $data = $request->safe()->collect();

        $tournament->update($data->only('status')->toArray());

        return response()->json($tournament);
    }

    public function updatePhaseStatus(Request $request, Tournament $tournament, TournamentPhase $tournamentPhase): JsonResponse
    {
        abort_unless($tournamentPhase->tournament_id === $tournament->id, 404);
        $data = $request->validate([
            'is_active' => 'sometimes|boolean',
            'is_completed' => 'sometimes|boolean',
        ]);

        if (array_key_exists('is_active', $data) && $data['is_active']) {
            // hacer exclusiva la fase activa
            $tournament->tournamentPhases()->update(['is_active' => false]);
        }
        $tournamentPhase->update($data);

        return response()->json([
            'phase' => $tournamentPhase->load('phase'),
            'all' => $tournament->tournamentPhases()->with('phase')->get(),
        ]);
    }

    public function advancePhase(Request $request, Tournament $tournament): JsonResponse
    {
        $currentPhase = $tournament->tournamentPhases()
            ->with('phase')
            ->where('is_active', true)
            ->first();

        if (!$currentPhase) {
            abort(422, 'No hay una fase activa para avanzar.');
        }

        $hasPendingGames = Game::where('tournament_id', $tournament->id)
            ->where('tournament_phase_id', $currentPhase->id)
            ->where('status', '!=', Game::STATUS_COMPLETED)
            ->exists();

        if ($hasPendingGames) {
            abort(422, 'Aún hay partidos pendientes en la fase actual.');
        }

        $phases = $tournament->tournamentPhases()->with('phase')->orderBy('id')->get();
        $currentIndex = $phases->search(fn($phase) => $phase->id === $currentPhase->id);
        $teamsCount = $tournament->teams()->count();

        $nextPhase = null;
        if ($currentIndex !== false) {
            for ($i = $currentIndex + 1; $i < $phases->count(); $i++) {
                $candidate = $phases[$i];
                if ($candidate->is_completed) {
                    continue;
                }
                $minTeams = $candidate->phase?->min_teams_for;
                if (!is_null($minTeams) && $teamsCount < $minTeams) {
                    continue;
                }
                $nextPhase = $candidate;
                break;
            }
        }

        $champion = null;

        DB::transaction(function () use ($tournament, $currentPhase, $nextPhase, &$champion) {
            TournamentFieldReservation::where('tournament_id', $tournament->id)->delete();

            $currentPhase->update([
                'is_active' => false,
                'is_completed' => true,
            ]);

            if ($nextPhase) {
                $nextPhase->update([
                    'is_active' => true,
                ]);
            } else {
                $champion = $this->resolveTournamentChampion($tournament, $currentPhase);
                $tournament->status = 'completado';
                if ($champion) {
                    $tournament->winner = $champion['team_name'];
                }
                $tournament->save();
            }
        });

        $currentPhase->refresh();
        if ($nextPhase) {
            $nextPhase->refresh();
        }
        $tournament->refresh();
        $phases = $tournament->tournamentPhases()->with('phase')->orderBy('id')->get();
        $message = $nextPhase
            ? sprintf('Fase "%s" activada.', $nextPhase->phase->name)
            : ($champion
                ? sprintf('Torneo finalizado. Campeón: %s.', $champion['team_name'])
                : 'Todas las fases del torneo han sido completadas.');

        return response()->json([
            'message' => $message,
            'current_phase' => $currentPhase->load('phase'),
            'next_phase' => $nextPhase ? $nextPhase->load('phase') : null,
            'phases' => $phases,
            'champion' => $champion,
            'tournament' => $tournament->only(['id', 'name', 'status', 'winner']),
        ]);
    }

    protected function resolveTournamentChampion(Tournament $tournament, TournamentPhase $completedPhase): ?array
    {
        $phaseName = $completedPhase->phase?->name;

        if ($phaseName === 'Final') {
            $finalMatch = Game::with('winnerTeam')
                ->where('tournament_id', $tournament->id)
                ->where('tournament_phase_id', $completedPhase->id)
                ->where('status', Game::STATUS_COMPLETED)
                ->orderByDesc('match_date')
                ->orderByDesc('match_time')
                ->orderByDesc('id')
                ->first();

            if ($finalMatch && $finalMatch->winnerTeam) {
                return [
                    'team_id' => $finalMatch->winnerTeam->id,
                    'team_name' => $finalMatch->winnerTeam->name,
                    'game_id' => $finalMatch->id,
                ];
            }
        }

        $standing = $tournament->standings()
            ->where('tournament_phase_id', $completedPhase->id)
            ->with('team')
            ->orderBy('rank')
            ->first();

        if ($standing && $standing->team) {
            return [
                'team_id' => $standing->team->id,
                'team_name' => $standing->team->name,
                'standing_id' => $standing->id,
            ];
        }

        return null;
    }

    public function scheduleSettings(Tournament $tournament): ScheduleSettingsResource
    {
        $formatName = $tournament->format->name;
        $teamsCount = $tournament->teams()
            ->withoutGlobalScopes()
            ->count();

        $resource = $tournament->load([
            'configuration',
            'format',
            'footballType',
            'locations',
            'tournamentPhases.phase' => function ($query) use ($formatName, $teamsCount) {
                $query->when(
                    $formatName === 'Grupos y Eliminatoria' || $formatName === 'Liga y Eliminatoria',
                    function ($q) use ($formatName, $teamsCount) {
                        $q->where(function ($nested) use ($formatName, $teamsCount) {
                            $fallbackPhase = $formatName === 'Grupos y Eliminatoria' ? 'Fase de grupos' : 'Tabla general';

                            $nested->whereNull('phases.min_teams_for')
                                ->orWhere('phases.min_teams_for', '<=', $teamsCount)
                                ->orWhere('phases.name', $fallbackPhase);
                        });
                    }
                );
            },
                'tournamentPhases.rules'
                ]
        );

        return new ScheduleSettingsResource($resource);
    }

    public function getTournamentLocations(int $tournamentId): JsonResponse
    {
        $tournament = Tournament::with('locations.tags')->where('id', $tournamentId)->firstOrFail();
        return response()->json($tournament->locations);
    }

    public function storeTournamentLocations(Request $request, Tournament $tournament): JsonResponse
    {
        $requestLocation = $request->location['location'];
        $location = $tournament->locations()->save(Location::updateOrCreate([
            'place_id' => $requestLocation['place_id']
        ], [
            'name' => $requestLocation['structured_formatting']['main_text'],
            'address' => $requestLocation['description'],
        ]));
        $location->syncTags($request->tags);
        return response()->json($location);
    }

    public function getTournamentSchedule(Request $request, int $tournamentId): JsonResponse
    {


        $filterBy = $request->get('filterBy', false);
        $search = $request->get('search', false);
        $page = (int)$request->get('page', 1);
        $per_page = 1;
        $skip = ($page - 1) * $per_page;

        $tournament = Tournament::with(['configuration'])
            ->findOrFail($tournamentId);

        $activePhase = $tournament->activePhase();

        $baseQuery = Game::query()
            ->where('tournament_id', $tournamentId)
            ->when($activePhase, static function ($query, $phase) {
                $query->where('tournament_phase_id', $phase->id);
            })
            ->when(!$activePhase, static function ($query, $value) {
                $query->whereNull('tournament_phase_id');
            });

        $hasSchedule = (clone $baseQuery)->exists();

        if(!$hasSchedule){
            return response()->json([
                'rounds' => [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_rounds' => 0
                ],
                'hasSchedule' => false,
            ]);
        }

        $includeGroupData = (int)($tournament->configuration?->tournament_format_id ?? $tournament->tournament_format_id)
            === TournamentFormatId::GroupAndElimination->value;

        $teamGroupMap = [];
        $groupSummaries = collect();
        $teamsById = collect();

        if ($includeGroupData) {
            $assignments = DB::table('team_tournament')
                ->select('team_id', 'group_key')
                ->where('tournament_id', $tournamentId)
                ->whereNotNull('group_key')
                ->get();

            if ($assignments->isNotEmpty()) {
                $teamIds = $assignments->pluck('team_id')->unique();
                $teamsById = Team::whereIn('id', $teamIds)
                    ->get(['id', 'name', 'image', 'colors'])
                    ->keyBy('id');

                $teamGroupMap = $assignments->pluck('group_key', 'team_id')->toArray();

                $groupSummaries = collect($teamGroupMap)
                    ->mapToGroups(static function ($groupKey, $teamId) {
                        return [$groupKey => $teamId];
                    })
                    ->map(function ($teamIds, $groupKey) use ($teamsById) {
                        $groupTeams = $teamIds->map(function ($teamId) use ($teamsById) {
                            $team = $teamsById->get($teamId);

                            if (!$team) {
                                return null;
                            }

                            return [
                                'id' => $team->id,
                                'name' => $team->name,
                                'image' => $team->image,
                            ];
                        })->filter()->values()->all();

                        return [
                            'key' => $groupKey,
                            'name' => "Grupo {$groupKey}",
                            'teams_count' => count($groupTeams),
                            'teams' => $groupTeams,
                        ];
                    });
            }
        }

        $schedule = (clone $baseQuery)
            ->with([
                'homeTeam',
                'awayTeam',
                'field',
                'location',
                'referee',
                'tournament',
                'tournament.configuration',
                // Pre-cargamos la plantilla de equipos para identificar al club que descansa en jornadas impares.
                'tournament.teams:id,name,image',
                'tournamentPhase',
                'tournamentPhase.phase',
            ])
            ->when($filterBy, static function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($search, static function ($query, $term) {
                $query->where(static function ($nested) use ($term) {
                    $nested->whereHas('awayTeam', static fn($teamQuery) => $teamQuery->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('homeTeam', static fn($teamQuery) => $teamQuery->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderBy('round')
            ->get()
            ->groupBy('round')
            ->slice($skip, $per_page)
            ->flatten();

        if ($includeGroupData && !empty($teamGroupMap)) {
            $buildGroupSummary = static function (string $groupKey) use ($teamGroupMap, $teamsById) {
                $teamIds = array_keys($teamGroupMap, $groupKey, true);

                $groupTeams = collect($teamIds)
                    ->map(function ($teamId) use ($teamsById) {
                        $team = $teamsById->get((int)$teamId);

                        if (!$team) {
                            return null;
                        }

                        return [
                            'id' => $team->id,
                            'name' => $team->name,
                            'image' => $team->image,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'key' => $groupKey,
                    'name' => "Grupo {$groupKey}",
                    'teams_count' => count($groupTeams),
                    'teams' => $groupTeams,
                ];
            };

            $schedule = $schedule->map(function (Game $game) use ($teamGroupMap, $groupSummaries, $buildGroupSummary) {
                $homeGroup = $teamGroupMap[$game->home_team_id] ?? null;
                $awayGroup = $teamGroupMap[$game->away_team_id] ?? null;
                $gameGroupKey = $game->group_key ?? null;

                if (!is_null($homeGroup)) {
                    $game->setAttribute('home_group_key', $homeGroup);
                }

                if (!is_null($awayGroup)) {
                    $game->setAttribute('away_group_key', $awayGroup);
                }

                if (!$gameGroupKey && $homeGroup && $homeGroup === $awayGroup) {
                    $gameGroupKey = $homeGroup;
                }

                if ($gameGroupKey) {
                    $summary = $groupSummaries->get($gameGroupKey) ?? $buildGroupSummary($gameGroupKey);
                    $game->setAttribute('group_key', $gameGroupKey);
                    $game->setAttribute('group_summary', $summary);
                }

                return $game;
            });
        }
        $latestLog = ScheduleRegenerationLog::query()
            ->where('tournament_id', $tournamentId)
            ->latest()
            ->first();

        $pendingManualMatches = Game::query()
            ->where('tournament_id', $tournamentId)
            ->where(static function ($query) {
                $query->whereNull('match_date')
                    ->orWhereNull('field_id');
            })
            ->count();

        return response()->json([
            'rounds' => TournamentScheduleCollection::make($schedule)->toArray($request),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_rounds' => (clone $baseQuery)
                    ->when($filterBy, static function ($query, $status) {
                        $query->where('status', $status);
                    })
                    ->when($search, static function ($query, $term) {
                        $query->where(static function ($nested) use ($term) {
                            $nested->whereHas('awayTeam', static fn($teamQuery) => $teamQuery->where('name', 'like', "%{$term}%"))
                                ->orWhereHas('homeTeam', static fn($teamQuery) => $teamQuery->where('name', 'like', "%{$term}%"));
                        });
                    })
                    ->distinct('round')->count('round'),
            ],
            'hasSchedule' => $hasSchedule,
            'regeneration' => $latestLog ? [
                'mode' => $latestLog->mode,
                'cutoff_round' => $latestLog->cutoff_round,
                'executed_at' => optional($latestLog->created_at)->toIso8601String(),
            ] : null,
            'pending_manual_matches' => $pendingManualMatches,
        ]);
    }

    public function analyzeScheduleRegeneration(
        Request $request,
        Tournament $tournament,
        TournamentScheduleRegenerationService $service
    ): JsonResponse {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'round_trip' => ['nullable', 'boolean'],
        ]);
        $roundTripSelected = array_key_exists('round_trip', $data)
            ? (bool)$data['round_trip']
            : null;
        $requestedStartDate = !empty($data['start_date'])
            ? Carbon::parse($data['start_date'])->startOfDay()
            : null;
        if ($requestedStartDate && $service->canUpdateStartDate($tournament)) {
            $tournament->start_date = $requestedStartDate;
        }
        $tournament->loadCount('teams');
        $analysis = $service->analyze($tournament, $roundTripSelected);

        return response()->json($analysis);
    }

    public function confirmScheduleRegeneration(
        Request $request,
        Tournament $tournament,
        TournamentScheduleRegenerationService $service
    ): JsonResponse {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'round_trip' => ['nullable', 'boolean'],
        ]);
        $requestedStartDate = !empty($data['start_date'])
            ? Carbon::parse($data['start_date'])->startOfDay()
            : null;
        $roundTripProvided = array_key_exists('round_trip', $data);
        $roundTripSelected = $roundTripProvided ? (bool)$data['round_trip'] : null;
        $canUpdateStartDate = $service->canUpdateStartDate($tournament);
        if ($requestedStartDate && !$canUpdateStartDate) {
            throw ValidationException::withMessages([
                'start_date' => ['No es posible actualizar la fecha de inicio porque la jornada 1 ya ha comenzado.'],
            ]);
        }
        if ($requestedStartDate && $canUpdateStartDate) {
            $currentStartDate = optional($tournament->start_date)?->copy()?->startOfDay();
            if (!$currentStartDate || !$currentStartDate->equalTo($requestedStartDate)) {
                $tournament->update([
                    'start_date' => $requestedStartDate,
                ]);
            }
        }
        if ($roundTripProvided) {
            $configuration = $tournament->configuration;
            if ($configuration) {
                $configuration->forceFill([
                    'round_trip' => $roundTripSelected,
                ])->save();
            } else {
                TournamentConfiguration::create([
                    'tournament_id' => $tournament->id,
                    'round_trip' => $roundTripSelected,
                ]);
            }
        }
        $tournament->refresh();
        $tournament->load(['configuration']);
        $tournament->loadCount('teams');
        try {
            $analysis = $service->analyze($tournament);
            $result = $service->regenerate($tournament, $analysis);
            $postAnalysis = $service->analyze($tournament);

            return response()->json(array_merge($result, [
                'analysis' => $postAnalysis,
            ]));
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'regeneration' => [$exception->getMessage()],
            ]);
        }
    }

    public function schedule(CreateTournamentScheduleRequest $request, Tournament $tournament): JsonResponse
    {
        /** @var ScheduleGeneratorService $service */
        $service = app(ScheduleGeneratorService::class);
        $matches = $service->setTournament($tournament)
            ->enableGroupStageMode($request->has('group_phase'))
            ->saveConfiguration($request->validated())
            ->makeSchedule();
        $service->persistScheduleToMatchSchedules($matches);

        return response()->json(['message' => 'Calendario generado correctamente', 'data' => $matches]);
    }

    public function updateTournamentRound(UpdateTournamentRoundRequest $request, Tournament $tournament, int $roundId): JsonResponse
    {
        $data = $request->validated();
        $matches = $data['matches'] ?? [];

        foreach ($matches as $match) {
            $game = $tournament->games()
                ->with(['tournament', 'tournamentPhase.phase'])
                ->where('round', $roundId)
                ->where('id', $match['id'])
                ->first();

            if (!$game) {
                continue;
            }

            $homeGoals = (int) data_get($match, 'home.goals', 0);
            $awayGoals = (int) data_get($match, 'away.goals', 0);
            $penalties = data_get($match, 'penalties');
            $applyPenaltyRule = $game->tournament->penalty_draw_enabled && !$this->isEliminationPhase($game);

            $game->home_goals = $homeGoals;
            $game->away_goals = $awayGoals;
            $game->status = Game::STATUS_COMPLETED;

            if ($applyPenaltyRule && $homeGoals === $awayGoals) {
                $decided = (bool) data_get($penalties, 'decided', false);
                if (!$decided) {
                    throw ValidationException::withMessages([
                        'matches' => ['Se requiere registrar el resultado de penales para los empates en este torneo.'],
                    ]);
                }

                $winnerTeamId = (int) data_get($penalties, 'winner_team_id');
                if (!in_array($winnerTeamId, [$game->home_team_id, $game->away_team_id], true)) {
                    throw ValidationException::withMessages([
                        'matches' => ['El equipo ganador en penales es inválido.'],
                    ]);
                }

                $penaltyHomeGoals = data_get($penalties, 'home_goals');
                $penaltyAwayGoals = data_get($penalties, 'away_goals');

                if (!is_numeric($penaltyHomeGoals) || !is_numeric($penaltyAwayGoals)) {
                    throw ValidationException::withMessages([
                        'matches' => ['El marcador de penales es obligatorio.'],
                    ]);
                }

                $penaltyHomeGoals = (int) $penaltyHomeGoals;
                $penaltyAwayGoals = (int) $penaltyAwayGoals;

                if ($winnerTeamId === $game->home_team_id && $penaltyHomeGoals <= $penaltyAwayGoals) {
                    throw ValidationException::withMessages([
                        'matches' => ['El marcador de penales no coincide con el equipo ganador.'],
                    ]);
                }

                if ($winnerTeamId === $game->away_team_id && $penaltyAwayGoals <= $penaltyHomeGoals) {
                    throw ValidationException::withMessages([
                        'matches' => ['El marcador de penales no coincide con el equipo ganador.'],
                    ]);
                }

                $game->decided_by_penalties = true;
                $game->penalty_winner_team_id = $winnerTeamId;
                $game->penalty_home_goals = $penaltyHomeGoals;
                $game->penalty_away_goals = $penaltyAwayGoals;
            } else {
                $game->decided_by_penalties = false;
                $game->penalty_winner_team_id = null;
                $game->penalty_home_goals = null;
                $game->penalty_away_goals = null;
            }

            $game->save(); // ← this will trigger GameObserver::updating / updated / saving / saved
        }
        return response()->json(['message' => 'Partido actualizado correctamente']);
    }

    public function setTournamentRoundBye(
        SetTournamentRoundByeRequest $request,
        Tournament $tournament,
        int $roundId
    ): JsonResponse {
        // Nota: este flujo no se está utilizando actualmente en la UI.
        $byeTeamId = (int) $request->validated()['bye_team_id'];

        RegenerateScheduleWithForcedByeJob::dispatch(
            $tournament->id,
            $roundId,
            $byeTeamId,
            auth()->id()
        )->afterCommit();

        return response()->json([
            'message' => 'La regeneración del calendario fue enviada a la cola.',
        ], 202);
    }

    public function setTournamentRoundSchedule(
        SetTournamentRoundScheduleRequest $request,
        Tournament $tournament,
        int $roundId
    ): JsonResponse {
        $data = $request->validated();
        $matches = $data['matches'] ?? [];
        $byeTeamId = isset($data['bye_team_id']) ? (int) $data['bye_team_id'] : null;

        RegenerateScheduleWithFixedRoundJob::dispatch(
            $tournament->id,
            $roundId,
            $matches,
            $byeTeamId,
            auth()->id()
        )->afterCommit();

        return response()->json([
            'message' => 'La regeneración del calendario fue enviada a la cola.',
        ], 202);
    }

    private function isEliminationPhase(Game $game): bool
    {
        $phaseName = optional($game->tournamentPhase?->phase)->name;

        if (!$phaseName) {
            return false;
        }

        return in_array($phaseName, [
            'Dieciseisavos de Final',
            'Octavos de Final',
            'Cuartos de Final',
            'Semifinales',
            'Final',
        ], true);
    }

    public function updateGameStatus(Request $request, int $tournamentId, int $roundId): JsonResponse
    {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    Game::STATUS_SCHEDULED,
                    Game::STATUS_IN_PROGRESS,
                    Game::STATUS_COMPLETED,
                    Game::STATUS_POSTPONED,
                    Game::STATUS_CANCELED,
                ]),
            ],
        ]);

        $status = $data['status'];

        Log::info('TournamentController::updateGameStatus:start', [
            'tournament_id' => $tournamentId,
            'round_id' => $roundId,
            'status' => $status,
            'actor_id' => $request->user()?->id,
        ]);

        $games = Game::where('tournament_id', $tournamentId)
            ->where('round', $roundId)
            ->get();

        foreach ($games as $game) {
            if ($game->status === $status) {
                Log::debug('TournamentController::updateGameStatus:skip-same-status', [
                    'game_id' => $game->id,
                    'status' => $status,
                ]);
                continue;
            }

            Log::info('TournamentController::updateGameStatus:updating', [
                'game_id' => $game->id,
                'previous_status' => $game->status,
                'new_status' => $status,
            ]);
            $game->status = $status;
            $game->save();
        }

        Log::info('TournamentController::updateGameStatus:completed', [
            'tournament_id' => $tournamentId,
            'round_id' => $roundId,
            'status' => $status,
            'games_updated' => $games->count(),
        ]);

        return response()->json(['message' => 'Estado de partido actualizado correctamente']);
    }

    public function fields(Request $request, Tournament $tournament): AnonymousResourceCollection
    {
        $locationId = $request->integer('location_id');

        $tournamentFieldsQuery = $tournament->fields()->with('location');

        if ($locationId) {
            $tournamentFieldsQuery->where('fields.location_id', $locationId);
        }

        $tournamentFields = $tournamentFieldsQuery->get();

        $fields = collect();
        $fieldsSource = null;

        if ($tournamentFields->isNotEmpty()) {
            $fields = $tournamentFields;
            $fieldsSource = 'tournament';
        } else {
            $league = $tournament->league;
            if ($league) {
                $leagueFieldsQuery = $league->fields()->with('location');
                if ($locationId) {
                    $leagueFieldsQuery->where('fields.location_id', $locationId);
                }
                $leagueFields = $leagueFieldsQuery->get();

                if ($leagueFields->isNotEmpty()) {
                    $fields = $leagueFields;
                    $fieldsSource = 'league';
                }
            }
        }

        return FieldResource::collection($fields)
            ->additional([
                'meta' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'fields_source' => $fieldsSource,
                ]
            ]);
    }

    /**
     * @throws \Throwable
     */
    public function exportTournamentRoundScheduleAs(Request $request, Tournament $tournament, int $round)
    {
        $type = $request->query('type');
        $games = Game::query()
            ->select([
                'id',
                'tournament_id',
                'league_id',
                'home_team_id',
                'away_team_id',
                'location_id',
                'match_date',
                'match_time',
                'round',
            ])
            ->where('tournament_id', $tournament->id)
            ->where('round', $round)
            ->with([
                'homeTeam:id,name,image',
                'awayTeam:id,name,image',
                'location:id,name',
            ])
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->get();
        $league = $tournament?->league;
        // Al exportar necesitamos saber qué equipo queda libre en rondas con número impar de participantes.
        $tournament->loadMissing('teams:id,name,image');

        $byeTeam = null;
        if ($tournament->teams->count() % 2 !== 0) {
            $playingTeamIds = $games
                ->flatMap(static fn($game) => [$game->home_team_id, $game->away_team_id])
                ->filter()
                ->unique();

            $candidate = $tournament->teams->first(static function ($team) use ($playingTeamIds) {
                return !$playingTeamIds->contains($team->id);
            });

            if ($candidate) {
                $byeTeam = $candidate;
            }
        }

        $exportable = null;
       if ($type === self::IMG_EXPORT_TYPE){
           $exportable = SnappyImage::loadView('exports.image.default', [
               'games' => $games,
               'tournament' => $tournament,
               'round' => $round,
               'league' => $league,
               'byeTeam' => $byeTeam,
           ])
               ->setOption('width', 794)
               ->setOption('height', 1123)
               ->setOption('format', 'jpg')
               ->setOption('quality', 85)
               ->setOption('encoding', 'UTF-8')
               ->setOption('disable-javascript', true)
               ->setOption('load-error-handling', 'ignore')
               ->setOption('load-media-error-handling', 'ignore')
               ->setOption('enable-local-file-access', true)
               ->download("jornada-$round-torneo-$tournament->slug.jpg");
       }
        if ($type === self::XSL_EXPORT_TYPE){
            sleep(2);
            $games = $games->map(function($game){
                return [
                    $game->homeTeam->name, $game->match_time, $game->awayTeam->name,
                ];
            })->toArray();
            $export = new RoundExport(
                $games,
                $round,
                $league->name,
                $tournament->name,
                $byeTeam?->name
            );
            $exportable =  Excel::download($export,"jornada-$round-torneo-$tournament->slug.xlsx");
        }
        return $exportable;
    }
    public function getStandings(Tournament $tournament): array
    {
        $tournament->loadMissing(['format', 'tournamentPhases.phase']);

        $fallbackPhaseName = $tournament->format?->name === 'Grupos y Eliminatoria'
            ? 'Fase de grupos'
            : 'Tabla general';

        $fallbackPhase = $tournament->tournamentPhases
            ->first(static fn($phase) => $phase->phase?->name === $fallbackPhaseName);

        $activePhase = $tournament->activePhase();
        $targetPhaseId = $fallbackPhase?->id ?? $activePhase?->id;

        $query = $tournament
            ->standings()
            ->with('team')
            ->orderBy('rank');

        if (is_null($targetPhaseId)) {
            $query->whereNull('tournament_phase_id');
        } else {
            $query->where('tournament_phase_id', $targetPhaseId);
        }

        return $query
            ->get()
            ->toArray();
    }
    public function getStats(Tournament $tournament): array
    {
        $goals = DB::table('game_events')
            ->join('games','games.id','=','game_events.game_id')
            ->join('players','players.id','game_events.player_id')
            ->join('users','users.id','players.user_id')
            ->join('teams','teams.id','game_events.team_id')
            ->where('games.tournament_id', $tournament->id)
            ->whereIn('game_events.type',[GameEvent::GOAL, GameEvent::PENALTY])
            ->select(
                'players.id as player_id',
                'users.name as player_name',
                'teams.name as team_name',
                'teams.slug as team_slug',
                DB::raw("COALESCE(users.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(users.name, ' ', '+'))) as user_image"),
                DB::raw("COALESCE(teams.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(COALESCE(teams.slug, ''), ' ', '+'))) as team_image"),
                DB::raw('COUNT(*) as total' )
            )
            ->groupBy('players.id','users.name','teams.name','teams.image','teams.slug')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $assistance =  DB::table('game_events')
            ->join('games','games.id','game_events.game_id')
            ->join('players','players.id','game_events.related_player_id')
            ->join('teams','teams.id','game_events.team_id')
            ->join('users','users.id','players.user_id')
            ->where('games.tournament_id', $tournament->id)
            ->where('game_events.type',GameEvent::GOAL)
            ->whereNotNull('game_events.related_player_id')
            ->select(
                'players.id as player_id',
                'users.name as player_name',
                'teams.name as team_name',
                DB::raw("COALESCE(users.image, CONCAT('https://ui-avatars.com/api/?name=', users.name)) as user_image"),
                DB::raw("COALESCE(teams.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(COALESCE(teams.name, ''), ' ', '+'))) as team_image"),
                DB::raw('COUNT(*) as total' )
            )
            ->groupBy('players.id','users.name','teams.name','teams.image')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $yellowCards = DB::table('game_events')
            ->join('games','games.id','game_events.game_id')
            ->join('players','players.id','game_events.player_id')
            ->join('teams','teams.id','game_events.team_id')
            ->join('users','users.id','players.user_id')
            ->where('games.tournament_id', $tournament->id)
            ->whereIn('game_events.type',[GameEvent::YELLOW_CARD, GameEvent::YELLOW_CARD])
            ->whereNotNull('game_events.player_id')
            ->select(
                'players.id as player_id',
                'users.name as player_name',
                'teams.name as team_name',
                DB::raw("COALESCE(users.image, CONCAT('https://ui-avatars.com/api/?name=', users.name)) as user_image"),
                DB::raw("COALESCE(teams.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(COALESCE(teams.name, ''), ' ', '+'))) as team_image"),
                DB::raw('COUNT(*) as total' )
            )
            ->groupBy(
                'players.id',
                'users.name',
                'teams.name',
                'teams.image'
            )
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $redCards = DB::table('game_events')
            ->join('games','games.id','game_events.game_id')
            ->join('players','players.id','game_events.player_id')
            ->join('teams','teams.id','game_events.team_id')
            ->join('users','users.id','players.user_id')
            ->where('games.tournament_id', $tournament->id)
            ->whereIn('game_events.type',[GameEvent::RED_CARD])
            ->whereNotNull('game_events.player_id')
            ->select(
                'players.id as player_id',
                'users.name as player_name',
                'teams.name as team_name',
                DB::raw("COALESCE(users.image, CONCAT('https://ui-avatars.com/api/?name=', users.name)) as user_image"),
                DB::raw("COALESCE(teams.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(COALESCE(teams.name, ''), ' ', '+'))) as team_image"),
                DB::raw('COUNT(*) as total' )
            )
            ->groupBy('players.id','users.name','teams.name','teams.image')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        return [
            'goals' => $goals,
            'assistance' => $assistance,
            'red_cards' => $redCards,
            'yellow_cards' => $yellowCards
        ];
    }
    public function getLastResults(Request $request, Tournament $tournament): LastGamesCollection
    {
        $limit = $request->query('limit',3);

        $lastGames = $tournament->games()
            ->with(['homeTeam:id,name,image', 'awayTeam:id,name,image','location:id,name','field:id,name'])
            ->whereIn('status', [Game::STATUS_COMPLETED, Game::STATUS_CANCELED, Game::STATUS_POSTPONED])
            ->orderBy('match_date', 'desc')
            ->orderBy('match_time', 'desc')
            ->limit($limit)
            ->get();

        return new LastGamesCollection($lastGames);
    }
    public function getNextGames(Request $request, Tournament $tournament): NextGamesCollection
    {
        $limit = $request->query('limit',3);

        $nextGames = $tournament->games()
            ->with(['homeTeam:id,name,image', 'awayTeam:id,name,image','location:id,name','field:id,name'])
            ->where('status', Game::STATUS_SCHEDULED)
            ->orderBy('match_date', 'desc')
            ->orderBy('match_time', 'desc')
            ->limit($limit)
            ->get();

        return new NextGamesCollection($nextGames);
    }

    /**
     * @throws Exception
     * @throws \Throwable
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportStanding(Request $request, Tournament $tournament)
    {
        $type = $request->query('type');
        $standing = $this->getStandings($tournament);
        $league = $tournament?->league;
        $exportable = null;
        if ($type === self::IMG_EXPORT_TYPE){
            $html = view('exports.tournament.standing',[
                'standing' => $standing,
                'leagueName' => $league->name,
                'tournamentName' => $tournament->name,
                'currentRound' => $tournament->currentRound()['round'],
                'currentDate' => today()->translatedFormat('l d M Y'),
                'showDetails' => false,
            ])->render();

            $exportable = SnappyImage::loadHTML($html)
                ->setOption('width', 794)
                ->setOption('height', 1123)
                ->setOption('format', 'jpg')
                ->setOption('quality', 100)
                ->setOption('encoding', 'UTF-8')
                ->setOption('enable-local-file-access', true)
                ->download($tournament->slug."-tabla-de-posiciones.jpg");
        }
        if ($type === self::XSL_EXPORT_TYPE){
            $export = new TournamentStandingExport($standing, $league->name, $tournament->name, $tournament->currentRound());
            $exportable =  Excel::download($export,$tournament->slug."-tabla-de-posiciones.xlsx", \Maatwebsite\Excel\Excel::XLSX);
        }
        return $exportable;

    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportStats(Request $request, Tournament $tournament)
    {
        $type = $request->query('type');
        $stats = $this->getStats($tournament);
        $league = $tournament?->league;
        $exportable = null;
        if ($type === self::IMG_EXPORT_TYPE){
            $html = view('exports.tournament.stats',[
                'goals' => $stats['goals'],
                'assistance' => $stats['assistance'],
                'redCards' => $stats['red_cards'],
                'yellowCards' =>$stats['yellow_cards'],
                'leagueName' => $league->name,
                'tournamentName' => $tournament->name,
                'currentRound' => 2,
                'currentDate' => today()->translatedFormat('l d M Y'),
                'showDetails' => false,
                'showImages' => true,
            ]);
            $exportable = SnappyImage::loadHTML($html)
                ->setOption('width', 794)
                ->setOption('height', 1123)
                ->setOption('format', 'jpg')
                ->setOption('quality', 100)
                ->setOption('encoding', 'UTF-8')
                ->setOption('enable-local-file-access', true)
                ->download($tournament->slug."-estadísticas.jpg");
        }
        if ($type === self::XSL_EXPORT_TYPE){
            $export = new TournamentStatsExport($stats, $league->name, $tournament->name, $tournament->currentRound());
            $exportable =  Excel::download($export,$tournament->slug."-tabla-de-posiciones.xlsx", \Maatwebsite\Excel\Excel::XLSX);
        }
        return $exportable;
    }
    public function canRegister(Tournament $tournament): JsonResponse
    {
        $canRegister = $tournament->teams->count() < $tournament->configuration->max_teams;
        return response()->json(['canRegister' => $canRegister]);
    }
    public function catalogs(Tournament $tournament): array
    {
        $tournament->load(['category','league']);
        return [
            'tournament' => $tournament,
            'category' => $tournament->category,
        ];
    }

    public function qrCodeGenerate(Request $request, Tournament $tournament): JsonResponse
    {
        $typeKey = request()->query('key', 'team_registration');
        sleep(2);
        $config = QrConfiguration::query()
            ->join('qr_types', 'qr_configurations.qr_type_id', '=', 'qr_types.id')
            ->where('qr_configurations.league_id', $tournament->league_id)
            ->where('qr_types.key', $typeKey)
            ->select('qr_configurations.*')
            ->first();

        if (!$config) {
            return response()->json(['error' => 'No existe configuración de QR para este tipo.'], 404);
        }
        $qrValue = $tournament->register_link;
        $image = QrTemplateRendererService::render([
            'title' => 'Torneo',
            'subtitle' => $tournament->name,
            'description' => 'Escanear para registrarse',
            'background_color' => $config->background_color,
            'foreground_color' => $config->foreground_color,
//            'logo' => 'images/vertical/logo-08.png',
//            'logo' => 'images/horizontal/logo-12.png',
            'logo' => 'images/text only/logo-18.png',
            'qr_value' => $qrValue,
        ]);
        return response()->json([
            'image' => $image,
            'meta' => [
                'league_id' => $tournament->league_id,
                'tournament_id' => $tournament->id,
                'type' => $typeKey,
            ],
        ]);
    }

    public function getRoundDetails(Request $request, Tournament $tournament, int $round): JsonResponse
    {
        $tournament->loadMissing(['configuration', 'teams']);
        $activePhase = $tournament->activePhase();

        $baseQuery = Game::query()
            ->where('tournament_id', $tournament->id)
            ->where('round', $round)
            ->when($activePhase, static function ($query, $phase) {
                $query->where('tournament_phase_id', $phase->id);
            })
            ->when(!$activePhase, static function ($query) {
                $query->whereNull('tournament_phase_id');
            });

        if (!(clone $baseQuery)->exists()) {
            return response()->json([
                'message' => 'No hay partidos para esta jornada.',
            ], 404);
        }

        $includeGroupData = (int)($tournament->configuration?->tournament_format_id ?? $tournament->tournament_format_id)
            === TournamentFormatId::GroupAndElimination->value;

        $teamGroupMap = [];
        $groupSummaries = collect();
        $teamsById = collect();

        if ($includeGroupData) {
            $assignments = DB::table('team_tournament')
                ->select('team_id', 'group_key')
                ->where('tournament_id', $tournament->id)
                ->whereNotNull('group_key')
                ->get();

            if ($assignments->isNotEmpty()) {
                $teamIds = $assignments->pluck('team_id')->unique();
                $teamsById = Team::whereIn('id', $teamIds)
                    ->get(['id', 'name', 'image', 'colors'])
                    ->keyBy('id');

                $teamGroupMap = $assignments->pluck('group_key', 'team_id')->toArray();

                $groupSummaries = collect($teamGroupMap)
                    ->mapToGroups(static function ($groupKey, $teamId) {
                        return [$groupKey => $teamId];
                    })
                    ->map(function ($teamIds, $groupKey) use ($teamsById) {
                        $groupTeams = $teamIds->map(function ($teamId) use ($teamsById) {
                            $team = $teamsById->get($teamId);

                            if (!$team) {
                                return null;
                            }

                            return [
                                'id' => $team->id,
                                'name' => $team->name,
                                'image' => $team->image,
                            ];
                        })->filter()->values()->all();

                        return [
                            'key' => $groupKey,
                            'name' => "Grupo {$groupKey}",
                            'teams_count' => count($groupTeams),
                            'teams' => $groupTeams,
                        ];
                    });
            }
        }

        $matches = (clone $baseQuery)
            ->with([
                'homeTeam',
                'awayTeam',
                'field',
                'location',
                'referee',
                'tournament',
                'tournament.configuration',
                'tournament.teams:id,name,image',
                'tournamentPhase',
                'tournamentPhase.phase',
                'penalties',
            ])
            ->orderBy('match_time')
            ->get();

        if ($includeGroupData && !empty($teamGroupMap)) {
            $buildGroupSummary = static function (string $groupKey) use ($teamGroupMap, $teamsById) {
                $teamIds = array_keys($teamGroupMap, $groupKey, true);

                $groupTeams = collect($teamIds)
                    ->map(function ($teamId) use ($teamsById) {
                        $team = $teamsById->get((int)$teamId);

                        if (!$team) {
                            return null;
                        }

                        return [
                            'id' => $team->id,
                            'name' => $team->name,
                            'image' => $team->image,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'key' => $groupKey,
                    'name' => "Grupo {$groupKey}",
                    'teams_count' => count($groupTeams),
                    'teams' => $groupTeams,
                ];
            };

            $matches = $matches->map(function (Game $game) use ($teamGroupMap, $groupSummaries, $buildGroupSummary) {
                $homeGroup = $teamGroupMap[$game->home_team_id] ?? null;
                $awayGroup = $teamGroupMap[$game->away_team_id] ?? null;
                $gameGroupKey = $game->group_key ?? null;

                if (!is_null($homeGroup)) {
                    $game->setAttribute('home_group_key', $homeGroup);
                }

                if (!is_null($awayGroup)) {
                    $game->setAttribute('away_group_key', $awayGroup);
                }

                if (!$gameGroupKey && $homeGroup && $homeGroup === $awayGroup) {
                    $gameGroupKey = $homeGroup;
                }

                if ($gameGroupKey) {
                    $summary = $groupSummaries->get($gameGroupKey) ?? $buildGroupSummary($gameGroupKey);
                    $game->setAttribute('group_key', $gameGroupKey);
                    $game->setAttribute('group_summary', $summary);
                }

                return $game;
            });
        }

        $byeTeam = null;
        $teams = $tournament->teams;
        if ($teams instanceof \Illuminate\Support\Collection && $teams->count() % 2 !== 0) {
            $playingTeamIds = $matches->flatMap(static function ($match) {
                return [
                    $match->home_team_id,
                    $match->away_team_id,
                ];
            })->filter()->unique();

            $bye = $teams->first(function ($team) use ($playingTeamIds) {
                return !$playingTeamIds->contains($team->id);
            });

            if ($bye) {
                $byeTeam = [
                    'id' => $bye->id,
                    'name' => $bye->name,
                    'image' => $bye->image,
                ];
            }
        }

        return response()->json([
            'round' => (int) $round,
            'status' => RoundStatusService::getRoundStatus($tournament->id, $round),
            'isEditable' => false,
            'date' => optional($matches->first())->match_date?->toDateString(),
            'matches' => $matches->map(fn ($match) => GameResource::make($match))->values(),
            'bye_team' => $byeTeam,
        ]);
    }
}
