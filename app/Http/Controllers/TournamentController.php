<?php

namespace App\Http\Controllers;

use App\DTO\TournamentDTO;
use App\Events\TournamentCreatedEvent;
use App\Exports\RoundExport;
use App\Exports\TournamentStandingExport;
use App\Exports\TournamentStatsExport;
use App\Http\Requests\CreateTournamentScheduleRequest;
use App\Http\Requests\TournamentStoreRequest;
use App\Http\Requests\TournamentUpdateRequest;
use App\Http\Requests\UpdateTournamentRoundRequest;
use App\Http\Requests\UpdateTournamentStatusRequest;
use App\Http\Resources\FieldResource;
use App\Http\Resources\LastGamesCollection;
use App\Http\Resources\NextGamesCollection;
use App\Http\Resources\ScheduleSettingsResource;
use App\Http\Resources\TournamentCollection;
use App\Http\Resources\TournamentResource;
use App\Http\Resources\TournamentScheduleCollection;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Location;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use App\Services\ScheduleGeneratorService;
use Barryvdh\Snappy\Facades\SnappyImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class TournamentController extends Controller
{
    const string IMG_EXPORT_TYPE = 'img';
    const string XSL_EXPORT_TYPE = 'excel';
    public function index(Request $request): TournamentCollection
    {

        $tournaments = Tournament::withCount(['teams', 'players', 'games'])
            ->with([
                'format' => function ($query) {
                    $query->select('id', 'name');
                },
                'locations'
            ])
            ->paginate($request->get('per_page', 10), ['*'], 'page', $request->get('page', 1));

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
                'autocomplete_prediction->place_id' => $requestLocation['place_id']
            ], [
                'name' => $requestLocation['structured_formatting']['main_text'],
                'address' => $requestLocation['description'],
                'city' => $requestLocation['terms'][2]['value'],
                'autocomplete_prediction' => $requestLocation
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
        $tournamentFormats = TournamentFormat::query()->select('id', 'name', 'description')->get();

        return response()->json($tournamentFormats);
    }

    public function updateStatus(UpdateTournamentStatusRequest $request, Tournament $tournament): JsonResponse
    {

        $data = $request->safe()->collect();

        $tournament->update($data->only('status')->toArray());

        return response()->json($tournament);
    }

    public function scheduleSettings(int $tournamentId): ScheduleSettingsResource
    {
        $tournament = Tournament::with(['configuration', 'format', 'footballType', 'locations'])
            ->findOrFail($tournamentId);
        return new ScheduleSettingsResource($tournament);
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
            'autocomplete_prediction->place_id' => $requestLocation['place_id']
        ], [
            'name' => $requestLocation['structured_formatting']['main_text'],
            'address' => $requestLocation['description'],
            'city' => $requestLocation['terms'][2]['value'],
            'autocomplete_prediction' => $requestLocation
        ]));
        $location->syncTags($request->tags);
        return response()->json($location);
    }

    public function getTournamentSchedule(Request $request, int $tournamentId): JsonResponse
    {
        $filterBy = $request->get('filterBy', false);
        $search = $request->get('search', false);
        $page = (int)$request->get('page', 1);
        $perPage = 1;
        $skip = ($page - 1) * $perPage;
        $schedule = Game::where([
            'tournament_id' => $tournamentId
        ])
            ->when($filterBy, function ($query) use ($filterBy) {
                return $query->where('status', $filterBy);
            })
            ->when($search, function ($query) use ($search) {
                return $query->whereHas('awayTeam', fn($query) => $query->where('name', 'like', "%$search%"))
                    ->orWhereHas('homeTeam', fn($query) => $query->where('name', 'like', "%$search%"));
            })
            ->orderBy('round')
            ->get()
            ->groupBy('round')
            ->slice($skip, $perPage)
            ->flatten();
        return response()->json([
            'rounds' => TournamentScheduleCollection::make($schedule)->toArray($request),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_rounds' => Game::where([
                    'tournament_id' => $tournamentId,
                ])->when($filterBy, function ($query) use ($filterBy) {
                    $query->where('status', $filterBy);
                })
                    ->distinct('round')->count('round'),
            ]
        ]);
    }

    public function schedule(CreateTournamentScheduleRequest $request, Tournament $tournament): JsonResponse
    {
        $service = new ScheduleGeneratorService();
        $matches = $service->setTournament($tournament)
            ->saveConfiguration($request->validated())
            ->makeSchedule();
        $service->persistScheduleToMatchSchedules($matches);

        return response()->json(['message' => 'Calendario generado correctamente', 'data' => $matches]);
    }

    public function updateTournamentRound(UpdateTournamentRoundRequest $request, Tournament $tournament, int $roundId): JsonResponse
    {
        $data = $request->validated();
        $matches = $data['matches'];
        foreach ($matches as $match) {
            $game = $tournament->games()
                ->where('round', $roundId)
                ->where('id', $match['id'])
                ->first();
            if ($game){
                $game->home_goals = $match['home']['goals'];
                $game->away_goals = $match['away']['goals'];
                $game->status = Game::STATUS_COMPLETED;
                $game->save(); // ← this will trigger GameObserver::updating / updated / saving / saved
            }
        }
        return response()->json(['message' => 'Partido actualizado correctamente']);
    }

    public function updateGameStatus(Request $request, int $tournamentId, int $roundId): JsonResponse
    {
        $status = $request->input('status');

        Game::where('tournament_id', $tournamentId)
            ->where('round', $roundId)
            ->update(['status' => $status]);

        return response()->json(['message', 'Estado de partido actualizado correctamente']);
    }

    public function fields(Tournament $tournament): AnonymousResourceCollection
    {
        return FieldResource::collection($tournament->fields)
            ->additional([
                'meta' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name
                ]
            ]);
    }

    /**
     * @throws \Throwable
     */
    public function exportTournamentRoundScheduleAs(Request $request, Tournament $tournament, int $round)
    {
        $type = $request->query('type');
        $games = Game::where('tournament_id', $tournament->id)
           ->with([
               'homeTeam:id,name,image',
               'awayTeam:id,name,image',
               'location:id,name'
           ])
           ->where('round', $round)
           ->get();
        $league = $tournament?->league;
        $exportable = null;
       if ($type === self::IMG_EXPORT_TYPE){

           $html = view('exports.image.default',[
               'games' => $games,
               'tournament' => $tournament,
               'round' => $round,
               'league' => $league
           ])->render();

           $exportable =  SnappyImage::loadHTML($html)
               ->setOption('width', 794)
               ->setOption('height', 1123)
               ->setOption('format', 'jpg')
               ->setOption('quality', 100)
               ->setOption('encoding', 'UTF-8')
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
            $export = new RoundExport($games, $round, $league->name,$tournament->name);
            $exportable =  Excel::download($export,"jornada-$round-torneo-$tournament->slug.xlsx");
        }
        return $exportable;
    }
    public function getStandings(Tournament $tournament): array
    {
       return $tournament
           ->standings()
           ->where('tournament_phase_id', $tournament->activePhase()->id)
           ->with('team')
           ->orderBy('rank')
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
                'stats' => $stats,
                'leagueName' => $league->name,
                'tournamentName' => $tournament->name,
                'currentRound' => $tournament->currentRound()['round'],
                'currentDate' => today()->translatedFormat('l d M Y'),
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
}
