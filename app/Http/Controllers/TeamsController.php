<?php

namespace App\Http\Controllers;

use App\Events\RegisteredTeamCoach;
use App\Events\RegisteredTeamPresident;
use App\Exports\TeamsTemplateExport;
use App\Facades\QrTemplateRendererService;
use App\Http\Requests\ImportTeamsRequest;
use App\Http\Requests\TeamHomePreferencesRequest;
use App\Http\Requests\TeamStoreRequest;
use App\Http\Requests\TeamUpdateRequest;
use App\Http\Resources\DefaultLineupResource;
use App\Http\Resources\LastGamesCollection;
use App\Http\Resources\NextGamesCollection;
use App\Http\Resources\TeamCollection;
use App\Http\Resources\TeamResource;
use App\Models\Category;
use App\Models\DefaultLineup;
use App\Models\DefaultLineupPlayer;
use App\Models\Formation;
use App\Models\Game;
use App\Models\League;
use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\Player;
use App\Models\Position;
use App\Models\QrConfiguration;
use App\Models\Team;
use App\Models\Location;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class TeamsController extends Controller
{

    public function index(Request $request): TeamCollection
    {
        $teams = Team::with([
                'tournaments' => fn($q) => $q->orderBy('name', 'desc'),
                'homeLocation',
            ])
             ->orderBy('id','desc')
            ->paginate(
                $request->get('per_page', 10),
                ['*'],
                'page',
                $request->get('page', 1)
            );

        return new TeamCollection($teams);
    }

    public function list(): TeamCollection
    {
        $teams = Team::query()->get();
        return new TeamCollection($teams);
    }

    public function show($id): TeamResource
    {
        $team = request()->boolean('by_slug')
            ? Team::where('slug', $id)->firstOrFail()
            : Team::findOrFail($id);
        return new TeamResource($team);
    }

    /**
     * @throws Throwable
     */
    public function store(TeamStoreRequest $request): TeamResource|JsonResponse
    {

        $data = $request->validated();
        try {
            DB::beginTransaction();

            $president = $this->createOrUpdateUser($data['president'] ?? null, $request, 'president', 'dueño de equipo', RegisteredTeamPresident::class);
            $coach = $this->createOrUpdateUser($data['coach'] ?? null, $request, 'coach', 'entrenador', RegisteredTeamCoach::class);
            $teamPayload = $data['team'];
            $defaultHome = $teamPayload['default_home'] ?? null;
            unset($teamPayload['default_home']);

            $colors = $teamPayload['colors'] ?? null;
            if (is_string($colors)) {
                $colors = json_decode($colors, true, 512, JSON_THROW_ON_ERROR);
            }

            $homeLocationId = $this->normalizeHomeLocation($teamPayload['home_location_id'] ?? null);
            $homeDayOfWeek = array_key_exists('home_day_of_week', $teamPayload)
                ? $this->normalizeHomeDay($teamPayload['home_day_of_week'])
                : null;
            $homeStartTime = $this->normalizeHomeStartTime($teamPayload['home_start_time'] ?? null);

            $teamModel = Team::create([
                'name' => $teamPayload['name'],
                'president_id' => $president?->id,
                'coach_id' => $coach?->id,
                'colors' => $colors,
                'home_location_id' => $homeLocationId,
                'home_day_of_week' => $homeDayOfWeek,
                'home_start_time' => $homeStartTime,
            ]);
            if ($request->hasFile('team.image')) {
                $media = $teamModel
                    ->addMedia($request->file('team.image'))
                    ->toMediaCollection('team');
                $teamModel->update([
                    'image' => $media->getUrl('default'),
                ]);
            }
            $league_id = auth()?->user()?->league_id;
            if (!$league_id) {
                $league_id = Tournament::where('id', $teamPayload['tournament_id'])->firstOrFail()->league?->id;
            }
            $teamModel->leagues()->attach($league_id);
            $teamModel->categories()->attach($teamPayload['category_id']);
            $teamModel->tournaments()->attach($teamPayload['tournament_id'], $this->buildHomeDefaults($defaultHome));
            DB::commit();
            return new TeamResource($teamModel);
        } catch (\Exception $e) {
            DB::rollBack();
            logger('error', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }

    }

    /**
     * @throws Throwable
     */
    public function update(TeamUpdateRequest $request, $id)
    {

        try {
            $data = $request->validated();
            DB::beginTransaction();
            $team = Team::findOrFail($id);
            $teamPayload = $data['team'];
            $hasDefaultHomeKey = array_key_exists('default_home', $teamPayload);
            $defaultHomeRaw = $hasDefaultHomeKey ? $teamPayload['default_home'] : null;
            if (is_string($defaultHomeRaw)) {
                $defaultHome = json_decode($defaultHomeRaw, true, 512, JSON_THROW_ON_ERROR);
            } elseif (is_array($defaultHomeRaw)) {
                $defaultHome = $defaultHomeRaw;
            } else {
                $defaultHome = null;
            }
            unset($teamPayload['default_home']);

            if (!empty($data['president'])) {
                $team->president->update(['name' => $data['president']['name']]);
                if ($request->hasFile('president.image')) {

                    $media = $team->president
                        ->addMedia($request->file('president.image'))
                        ->toMediaCollection('image');
                    $team->president->update([
                        'image' => $media->getUrl(),
                    ]);
                }
            }
            if ( !empty($data['coach'])) {
                $team->coach->update(['name' => $data['coach']['name']]);
                if ($request->hasFile('coach.image')) {
                    $media = $team->coach
                        ->addMedia($request->file('coach.image'))
                        ->toMediaCollection('image');
                    logger('media', [
                        'coach url' => $media->getUrl(),
                    ]);
                    $team->coach->update([
                        'image' => $media->getUrl(),
                    ]);
                }
            }
            $colors = !empty($teamPayload['colors'])
                ? (is_array($teamPayload['colors'])
                    ? $teamPayload['colors']
                    : json_decode($teamPayload['colors'], true, 512, JSON_THROW_ON_ERROR))
                : null;

            $updatePayload = [
                'name' => $teamPayload['name'],
                'colors' => $colors,
            ];

            if (array_key_exists('home_location_id', $teamPayload)) {
                $updatePayload['home_location_id'] = $this->normalizeHomeLocation($teamPayload['home_location_id']);
            }

            if (array_key_exists('home_day_of_week', $teamPayload)) {
                $updatePayload['home_day_of_week'] = $this->normalizeHomeDay($teamPayload['home_day_of_week']);
            }

            if (array_key_exists('home_start_time', $teamPayload)) {
                $updatePayload['home_start_time'] = $this->normalizeHomeStartTime($teamPayload['home_start_time']);
            }

            $team->update($updatePayload);
            if ($request->hasFile('team.image')) {

                $media = $team
                    ->addMedia($request->file('team.image'))
                    ->toMediaCollection('team');
                $team->update([
                    'image' => $media->getUrl('default'),
                ]);
            }
            $team->categories()->syncWithoutDetaching([$teamPayload['category_id']]);

            if ($hasDefaultHomeKey) {
                $team->tournaments()->syncWithoutDetaching([
                    $teamPayload['tournament_id'] => $this->buildHomeDefaults($defaultHome, true),
                ]);
            } else {
                $team->tournaments()->syncWithoutDetaching([$teamPayload['tournament_id']]);
            }
            $team->refresh();
            DB::commit();
            return new TeamResource($team);
        } catch (\Exception $e) {
            DB::rollBack();
            logger('error', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    public function updateHomePreferences(TeamHomePreferencesRequest $request, Team $team): TeamResource
    {
        $payload = $request->validated();

        $homeLocationId = $this->normalizeHomeLocation($payload['home_location_id'] ?? null);

        // Validamos que la sede exista dentro de la liga actual antes de continuar.
        $this->assertLocationBelongsToLeague($homeLocationId);

        // Normalizamos la información antes de persistirla para mantener consistencia en la base de datos.
        $attributes = [
            'home_location_id' => $homeLocationId,
            'home_day_of_week' => $this->normalizeHomeDay($payload['home_day_of_week'] ?? null),
            'home_start_time' => $this->normalizeHomeStartTime($payload['home_start_time'] ?? null),
        ];

        DB::transaction(static function () use ($team, $attributes) {
            $team->update($attributes);
        });

        $team->refresh()->loadMissing(['homeLocation']);

        return new TeamResource($team);
    }

    public function destroy($id): void
    {

    }

    private function createOrUpdateUser($userData, $request, $role, $roleName, $eventClass, $sendEmail = true, $saveQuietly = false): ?User
    {
        if (!$userData) {
            return null;
        }

        $user = collect($userData);
        $temporaryPassword = str()->random(8);
        $user->put('password', $temporaryPassword);
        $user->put('verified_at', now());
        if($saveQuietly){
            $user = User::withoutEvents(function () use ($user) {
                return User::updateOrCreate(['email' => $user->get('email')], $user->except('email')->toArray());
            });
        } else {
           $user =  User::updateOrCreate(['email' => $user->get('email')], $user->except('email')->toArray());
        }


        if ($request->hasFile("$role.image")) {
            $media = $user->addMedia($request->file("$role.image"))->toMediaCollection('image');
            $user->update(['image' => $media->getUrl()]);
        }

        $league_id = auth()?->user()?->league_id;
        if (!$league_id) {
            $tournament_id = request()->input('team')['tournament_id'];
            if($tournament_id){
                $league_id = Tournament::where('id', $tournament_id)->firstOrFail()->league?->id;
                $user->league()->associate($league_id);
            }
        }

        $user->save();
        $user->assignRole($roleName);
        if ($sendEmail && app()->environment('production')) {
            event(new $eventClass($user, $temporaryPassword));
        }

        return $user;
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function downloadTeamsTemplate(): BinaryFileResponse
    {
        return Excel::download(new TeamsTemplateExport, 'plantilla_importacion_equipos.xlsx');
    }

    /**
     * @throws Throwable
     */
    public function import(ImportTeamsRequest $request): ?JsonResponse
    {

        try {
            $spreadsheet = IOFactory::load($request->file('file'));
            $tournament = Tournament::find($request->get('tournament_id'));
            $sheetNames = $spreadsheet->getSheetNames();
            $found = false;
            $teamsData = [];

            foreach ($sheetNames as $name) {
                $sheet = $spreadsheet->getSheetByName($name);
                if (!$sheet) {
                    continue;
                }

                $header = $sheet->rangeToArray('A1:K1', null, true, true, true)[1];

                if ($this->isValidHeader($header)) {
                    $found = true;
                    $rows = $sheet->toArray(null, true, true, true);
                    array_shift($rows); // quitar header
                    $teamsData = $rows;
                    break;
                }
            }
            if (!$found) {
                return response()->json([
                    'message' => 'No se encontró una hoja de datos válida. Asegúrese de que las columnas coincidan con el formato requerido.',
                ], 422);
            }
            DB::beginTransaction();
            foreach ($teamsData as $row) {
                if (!$row['A']){
                    continue;
                }
                $this->storeTeamFromRow($row, $tournament);
            }
            DB::commit();
            return response()->json(['message' => 'Equipos importados exitosamente.']);

        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'message' => 'Ocurrió un error procesando el archivo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function isValidHeader($header): bool
    {
        $expected = [
            'A' => 'Nombre del equipo',
//            'B' => 'Sede',
            'B' => 'Color local primario',
            'C' => 'Color local secundario',
            'D' => 'Color visitante primario',
            'E' => 'Color visitante secundario',
            'F' => 'Nombre del presidente',
            'G' => 'Teléfono del presidente',
            'H' => 'Correo del presidente',
            'I' => 'Nombre del entrenador',
            'J' => 'Teléfono del entrenador',
            'K' => 'Correo del entrenador',
        ];

        foreach ($expected as $column => $expectedValue) {
            if (trim($header[$column]) !== $expectedValue) {
                return false;
            }
        }
        return true;
    }

    /**
     * @throws JsonException
     */
    private function storeTeamFromRow($row, $tournament): void
    {


        $data = [
            'team' => [
                'name' => $row['A'],
//                'home_location_id' => $homeLocationId,
                'colors' => json_encode([
                    'home' => [
                        'primary' => $row['B'],
                        'secondary' => $row['C'],
                    ],
                    'away' => [
                        'primary' => $row['D'],
                        'secondary' => $row['E'],
                    ],
                ], JSON_THROW_ON_ERROR),
                'category_id' => $tournament->category->id,
                'tournament_id' => $tournament->id,
            ],
            'president' => [
                'name' => $row['F'],
                'phone' => $row['G'],
                'email' => $row['H'],
            ],
            'coach' => [
                'name' => $row['I'],
                'phone' => $row['J'],
                'email' => $row['K'],
            ],
        ];
        $formRequest = TeamStoreRequest::create('', 'POST', $data);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();
        $president = null;
        $coach = null;
        if ($data['president']['name']) {
            $president = $this->createOrUpdateUser(
                $formRequest['president'] ?? null,
                request(),
                'president',
                'dueño de equipo',
                RegisteredTeamPresident::class,
                false,
                true
            );
        }
        if ($data['coach']['name']) {
            $coach = $this->createOrUpdateUser(
                $formRequest['coach'] ?? null,
                request(),
                'coach',
                'entrenador',
                RegisteredTeamCoach::class,
                false,
                    true
            );
        }

        $team = Team::create([
            'name' => $data['team']['name'],
            'president_id' => $president?->id,
            'coach_id' => $coach?->id,
//            'home_location_id' => $data['team']['home_location_id'],
            'colors' => json_decode($data['team']['colors'], false, 512, JSON_THROW_ON_ERROR),
        ]);
        $team->leagues()->attach(auth()?->user()?->league_id);
        $team->categories()->attach($data['team']['category_id']);
        $team->tournaments()->attach($data['team']['tournament_id']);
    }

    /**
     * @throws JsonException
     */
    private function buildHomeDefaults(?array $defaults, bool $includeNulls = false): array
    {
        if ($defaults === null) {
            return $includeNulls ? [
                'home_location_id' => null,
                'home_field_id' => null,
                'home_day_of_week' => null,
                'home_start_time' => null,
            ] : [];
        }

        $payload = [
            'home_location_id' => $defaults['location_id'] ?? null,
            'home_field_id' => $defaults['field_id'] ?? null,
            'home_day_of_week' => array_key_exists('day_of_week', $defaults)
                ? $this->normalizeHomeDay($defaults['day_of_week'])
                : null,
            'home_start_time' => $this->normalizeHomeStartTime($defaults['start_time'] ?? null),
        ];

        if (!$includeNulls) {
            foreach ($payload as $key => $value) {
                if (is_null($value)) {
                    unset($payload[$key]);
                }
            }
        }

        return $payload;
    }

    private function normalizeHomeLocation(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function normalizeHomeDay(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $day = (int) $value;

        return ($day >= 0 && $day <= 6) ? $day : null;
    }

    private function normalizeHomeStartTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i', (string) $value)->format('H:i:s');
        } catch (\Throwable) {
            try {
                return Carbon::createFromFormat('H:i:s', (string) $value)->format('H:i:s');
            } catch (\Throwable) {
                return null;
            }
        }
    }

    private function assertLocationBelongsToLeague(?int $locationId): void
    {
        if (!$locationId) {
            return;
        }

        $leagueId = auth()->user()?->league_id;

        if (!$leagueId) {
            return;
        }

        // Aseguramos que la sede esté vinculada a la liga del usuario autenticado.
        $belongsToLeague = DB::table('league_location')
            ->where('league_id', $leagueId)
            ->where('location_id', $locationId)
            ->exists();

        if (!$belongsToLeague) {
            throw ValidationException::withMessages([
                'home_location_id' => ['La sede seleccionada no pertenece a tu liga.'],
            ]);
        }
    }

    public function search(Request $request): TeamCollection
    {
        $value = $request->get('value', false);
        $teams = Team::with('tournaments.configuration:id:max_players_per_team')
            ->withCount('players')
            ->when($value && $value !== '', fn($query) => $query->where('name', 'like', "%$value%"))
            ->paginate(10, ['*'], 'page', $request->get('page', 1));

        return new TeamCollection($teams);
    }

    public function assignPlayer(Request $request, $teamId, $playerId): JsonResponse
    {
        $team = Team::findOrFail($teamId);
        $player = Player::findOrFail($playerId);
        if ($team->players()->where('user_id', $player->id)->exists()) {
            return response()->json(['message' => 'El jugador ya está asignado a este equipo.'], 422);
        }
        $player->team_id = $team->id;
        $player->save();
        return response()->json(['message' => 'Jugador asignado al equipo correctamente.']);
    }

    public function formation(Request $request, Team  $team): DefaultLineupResource
    {
        return new DefaultLineupResource($team);
    }

    public function getDefaultLineupAvailableTeamPlayers(Request $request, Team $team): JsonResponse
    {
        $players = $team->players()
            ->doesntHave('defaultLineup')
            ->with('user')
            ->get()
            ->map(function ($player) {
                return [
                    'player_id' => $player->id,
                    'team_id' => $player->team_id,
                    'name' => $player->user?->name ?? '',
                    'number' => $player->number,
                    'position' => $player->position?->abbr ?? '',
                ];
            });
        return response()->json($players);
    }
    public function updateDefaultLineupAvailableTeamPlayers(Request $request, Team $team, DefaultLineupPlayer $defaultLineupPlayer): JsonResponse
    {
        $data = $request->validate([
            'player' => 'required',
            'player.player_id' => 'required|exists:players,id',
            'player.team_id' => 'required|exists:teams,id',
            'field_location' => 'required',
        ]);
        $defaultLineupPlayer->update([
            'player_id' => $data['player']['player_id'],
            'field_location' => $defaultLineupPlayer->field_location,
        ]);


        return response()->json([
            'message' => 'Jugador actualizado en la alineación por defecto del equipo.',
            'default_lineup_player' => $defaultLineupPlayer,
        ]);
    }
    public function nextGames(Request $request, Team $team): NextGamesCollection
    {
        $limit = $request->get('limit', 3);
        $nextGames = Game::where('away_team_id',$team->id)
            ->orWhere('home_team_id', $team->id)
            ->whereIn('status', ['programado', 'aplazado'])
            ->with(['homeTeam', 'homeTeam'])
            ->orderBy('match_date','asc')
            ->limit($limit)
            ->get();
        return new NextGamesCollection($nextGames);
    }
    public function lastGames(Request $request, Team $team): LastGamesCollection
    {
        $limit = $request->input('limit', 3);
        $orderBy = $request->input('order','asc');
        $nextGames = $team->games()
            ->with(['homeTeam:id,name,image', 'awayTeam:id,name,image','location:id,name','field:id,name'])
            ->whereIn('status', [Game::STATUS_COMPLETED, Game::STATUS_CANCELED, Game::STATUS_POSTPONED])
            ->orderBy('match_date',$orderBy)
            ->limit($limit)
            ->get();
        return new LastGamesCollection($nextGames);
    }
    public function addDefaultLineupPlayer(Request $request, Team $team): JsonResponse
    {
        $data = $request->validate([
            'player.player_id' => 'required|exists:players,id',
            'field_location' => 'required',
        ]);
        $player = Player::findOrFail($data['player']['player_id']);
        if ($player->team_id !== $team->id) {
            return response()->json(['message' => 'El jugador no pertenece a este equipo.'], 422);
        }
        $defaultLineupPlayer = DefaultLineupPlayer::create([
            'default_lineup_id' => $team->defaultLineup?->id,
            'player_id' => $data['player']['player_id'],
            'field_location' => $data['field_location'],
        ]);
        return response()->json([
            'message' => 'Jugador agregado a la alineación por defecto del equipo.',
            'default_lineup_player' => $defaultLineupPlayer,
        ]);
    }
    public function addLineupPlayer(Request $request, Team $team, Game $game): JsonResponse
    {
        $data = $request->validate([
            'player.player_id' => 'required|exists:players,id',
            'field_location' => 'required',
            'currentPlayer' => 'required',
        ]);

        $player = Player::findOrFail($data['player']['player_id']);
        if ($player->team_id !== $team->id) {
            return response()->json(['message' => 'El jugador no pertenece a este equipo.'], 422);
        }
        $lineup = Lineup::where('game_id', $game->id)
            ->where('team_id', $team->id)
            ->first();
        $lineupPlayer = LineupPlayer::query()
            ->where('lineup_id', $lineup?->id)
            ->where('field_location', $data['field_location'])
            ->first();
        if ($lineupPlayer) {
            $lineupPlayer->field_location = null;
            $lineupPlayer->is_headline = false;
            $lineupPlayer->save();
        }
        // Nos aseguramos de que el jugador exista en la alineación actual y lo marcamos como titular.
        $playerLineup = LineupPlayer::where('lineup_id', $lineup?->id)
            ->where('player_id', $data['player']['player_id'])
            ->first();

        if (!$playerLineup) {
            $playerLineup = LineupPlayer::create([
                'lineup_id' => $lineup?->id,
                'player_id' => $data['player']['player_id'],
                'field_location' => $data['field_location'],
                'is_headline' => true,
                'substituted' => false,
            ]);
        } else {
            // Si ya estaba asociado (por ejemplo, como cambio o suplente), actualizamos su posición y lo rehabilitamos.
            $playerLineup->field_location = $data['field_location'];
            $playerLineup->is_headline = true;
            $playerLineup->substituted = false;
            $playerLineup->save();
        }

        return response()->json([
            'message' => 'Jugador agregado a la alineación del partido.',
            'lineup_player' => $playerLineup,
        ]);
    }
    public function updateDefaultFormation(Request $request, Team $team): JsonResponse
    {
        $data = $request->validate([
            'formation_id' => 'required|exists:formations,id',
        ]);
        DefaultLineup::where('team_id', $team->id)
            ->update([
                'formation_id' => $data['formation_id'
            ]
        ]);
        return response()->json(['message' => 'Formación actualizada correctamente.', 'team' => $team]);
    }
    public function updateLineupAvailableTeamPlayers(Request $request, Team $team, LineupPlayer $lineupPlayer): JsonResponse
    {
        $data = $request->validate([
            'player' => 'required',
            'player.player_id' => 'required|exists:players,id',
            'player.team_id' => 'required|exists:teams,id',
            'field_location' => 'required',
        ]);

        $olDLineupPlayer = LineupPlayer::where('lineup_id', $lineupPlayer->lineup_id)
            ->where('player_id', $data['player']['player_id'])
            ->first();

        if ($olDLineupPlayer) {
            $olDLineupPlayer->is_headline = false;
            $olDLineupPlayer->player_id = $lineupPlayer->player_id;
            $olDLineupPlayer->save();
        }
        $lineupPlayer->player_id = $data['player']['player_id'];
        $lineupPlayer->field_location = $data['field_location'];
        $lineupPlayer->is_headline = true;
        $lineupPlayer->save();

        return response()->json([
            'message' => 'Jugador actualizado en la alineación del partido.',
            'lineup_player' => $lineupPlayer,
        ]);
    }

    public function updateGameTeamFormation(Request $request, Team $team, Game $game): JsonResponse
    {
        $data = $request->validate([
            'formation_id' => 'required|exists:formations,id',
        ]);
        $lineup = Lineup::where('game_id', $game->id)
            ->where('team_id', $team->id)
            ->first();
        if (!$lineup) {
            return response()->json(['message' => 'No se encontró la alineación para este partido.'], 404);
        }
        $lineup->update(['formation_id' => $data['formation_id']]);
        return response()->json(['message' => 'Formación actualizada correctamente.', 'lineup' => $lineup]);
    }
    public function catalogs(Team $team): array
    {
        $tournaments = $team->load(['tournaments:id,name','categories'])->tournaments;
        return [
            'team' => $team->only('id','name','slug','categories'),
            'tournaments' => $tournaments,
            'categories' => Category::select('id','name')->get(),
            'positions' => Position::select('id','name','abbr','type')->get()
        ];
    }
    public function canRegister(Team $team): JsonResponse
    {
        $canRegister = $team->players->count() < $team->tournaments()->first()->configuration->max_players_per_team;
        return response()->json(['canRegister' => $canRegister]);
    }
    public function qrCodeGenerate(Request $request, Team $team): JsonResponse
    {
        $typeKey = request()->query('key', 'player_registration');
        $leagueId = $team->leagues()->first()->id;
        sleep(1);
        $config = QrConfiguration::query()
            ->join('qr_types', 'qr_configurations.qr_type_id', '=', 'qr_types.id')
            ->where('qr_configurations.league_id', $leagueId)
            ->where('qr_types.key', $typeKey)
            ->select('qr_configurations.*')
            ->first();

        if (!$config) {
            return response()->json(['error' => 'No existe configuración de QR para este tipo.'], 404);
        }
        $qrValue = $team->register_link;
        $image = QrTemplateRendererService::render([
            'title' => 'Equipo',
            'subtitle' => $team->name,
            'description' => 'Escanear para registrarse',
            'background_color' => $config->background_color,
            'foreground_color' => $config->foreground_color,
            'logo' => 'images/text only/logo-18.png',
            'qr_value' => $qrValue,
        ]);
        return response()->json([
            'image' => $image,
            'meta' => [
                'league_id' => $leagueId,
                'tournament_id' => $team->id,
                'type' => $typeKey,
            ],
        ]);
    }

}
