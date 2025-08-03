<?php

namespace App\Http\Controllers;

use App\Events\RegisteredTeamCoach;
use App\Events\RegisteredTeamPresident;
use App\Exports\TeamsTemplateExport;
use App\Http\Requests\ImportTeamsRequest;
use App\Http\Requests\TeamStoreRequest;
use App\Http\Requests\TeamUpdateRequest;
use App\Http\Resources\DefaultLineupResource;
use App\Http\Resources\NextGamesCollection;
use App\Http\Resources\TeamCollection;
use App\Http\Resources\TeamResource;
use App\Models\DefaultLineup;
use App\Models\DefaultLineupPlayer;
use App\Models\Formation;
use App\Models\Game;
use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeamsController extends Controller
{

    public function index(Request $request): TeamCollection
    {
        $teams = Team::
        orderBy('teams.created_at', $request->get('sort', 'asc'))
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function show($id): TeamResource
    {
        $team = request()->boolean('by_slug')
            ? Team::where('slug', $id)->firstOrFail()
            : Team::findOrFail($id);
        return new TeamResource($team);
    }

    /**
     * @throws \Throwable
     */
    public function store(TeamStoreRequest $request): TeamResource|JsonResponse
    {

        $data = $request->validated();
        try {
            DB::beginTransaction();

            $president = $this->createOrUpdateUser($data['president'] ?? null, $request, 'president', 'dueño de equipo', RegisteredTeamPresident::class);
            $coach = $this->createOrUpdateUser($data['coach'] ?? null, $request, 'coach', 'entrenador', RegisteredTeamCoach::class);
            $team = $data['team'];
            $colors = $team['colors'] ?? [];
            $address = $team['address'] ?? null;
            if (is_string($colors)) {
                $colors = json_decode($colors, true, 512, JSON_THROW_ON_ERROR);
            }
            if (is_string($address)) {
                $address = json_decode($address, true, 512, JSON_THROW_ON_ERROR);
            }
            if (empty($colors)) {
                $colors = config('constants.colors');
            }

            $team = Team::create([
                'name' => $data['team']['name'],
                'president_id' => $president?->id ?? null,
                'coach_id' => $coach?->id ?? null,
                'phone' => $data['team']['phone'] ?? null,
                'email' => $data['team']['email'] ?? null,
                'address' => $address,
                'colors' => $colors,
            ]);
            if ($request->hasFile('team.image')) {
                $media = $team
                    ->addMedia($request->file('team.image'))
                    ->toMediaCollection('team');
                $team->update([
                    'image' => $media->getUrl('default'),
                ]);
            }
            $league_id = auth()?->user()?->league_id;
            if (!$league_id) {
                $league_id = Tournament::where('id', $data['team']['tournament_id'])->first()->league?->id;
            }
            $team->leagues()->attach($league_id);
            $team->categories()->attach($data['team']['category_id']);
            $team->tournaments()->attach($data['team']['tournament_id']);
            $defaultFormation = Formation::first();
            $team->defaultLineup()->create([
                'formation_id' =>$defaultFormation->id,
            ]);
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

    public function update(TeamUpdateRequest $request, $id)
    {

        try {
            $data = $request->validated();
            $president = collect($data['president']);
            $coach = collect($data['coach']);
            DB::beginTransaction();
            $team = Team::findOrFail($id);
            if ($president !== null) {
                $team->president->update($president->only('name')->toArray());
                if ($request->hasFile('president.image')) {

                    $media = $team->president
                        ->addMedia($request->file('president.image'))
                        ->toMediaCollection('image', 's3');
                    $team->president->update([
                        'image' => $media->getUrl(),
                    ]);
                }
            }
            if ($coach !== null) {
                $team->coach->update($coach->only('name')->toArray());
                if ($request->hasFile('coach.image')) {

                    $media = $team->coach
                        ->addMedia($request->file('coach.image'))
                        ->toMediaCollection('image', 's3');
                    logger('media', [
                        'coach url' => $media->getUrl(),
                    ]);
                    $team->coach->update([
                        'image' => $media->getUrl(),
                    ]);
                }
            }
            $team->update([
                'name' => $data['team']['name'],
                'address' => json_decode($data['team']['address']),
                'colors' => json_decode($data['team']['colors']),
            ]);
            if ($request->hasFile('team.image')) {

                $media = $team
                    ->addMedia($request->file('team.image'))
                    ->toMediaCollection('team');
                $team->update([
                    'image' => $media->getUrl('default'),
                ]);
            }
            $team->categories()->attach($data['team']['category_id']);
            $team->tournaments()->attach($data['team']['tournament_id']);
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

    public function destroy($id): void
    {

    }

    private function createOrUpdateUser($userData, $request, $role, $roleName, $eventClass, $sendEmail = true): ?User
    {
        if (!$userData) {
            return null;
        }

        $user = collect($userData);
        $temporaryPassword = str()->random(8);
        $user->put('password', $temporaryPassword);
        $user->put('verified_at', now());

        $user = User::updateOrCreate(['email' => $user->get('email')], $user->except('email')->toArray());

        if ($request->hasFile("$role.image")) {
            $media = $user->addMedia($request->file("$role.image"))->toMediaCollection('image');
            $user->update(['image' => $media->getUrl()]);
        }

        $user->league()->associate(auth()->user()->league);
        $user->save();
        $user->assignRole($roleName);
        if ($sendEmail) {
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
     * @throws \Throwable
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

                $header = $sheet->rangeToArray('A1:O1', null, true, true, true)[1];

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
            'B' => 'Correo del equipo',
            'C' => 'Teléfono del equipo',
            'D' => 'Dirección',
            'E' => 'Color local primario',
            'F' => 'Color local secundario',
            'G' => 'Color visitante primario',
            'H' => 'Color visitante secundario',
            'I' => 'Nombre del presidente',
            'J' => 'Teléfono del presidente',
            'K' => 'Correo del presidente',
            'L' => 'Nombre del entrenador',
            'M' => 'Teléfono del entrenador',
            'N' => 'Correo del entrenador',
        ];

        foreach ($expected as $column => $expectedValue) {
            if (trim($header[$column]) !== $expectedValue) {
                return false;
            }
        }
        return true;
    }

    /**
     * @throws \JsonException
     */
    private function storeTeamFromRow($row, $tournament): void
    {
        $data = [
            'team' => [
                'name' => $row['A'],
                'email' => $row['B'],
                'phone' => $row['C'],
                'address' => $this->normalizeAddress($row['D']),
                'colors' => json_encode([
                    'home' => [
                        'primary' => $row['E'],
                        'secondary' => $row['F'],
                    ],
                    'away' => [
                        'primary' => $row['G'],
                        'secondary' => $row['H'],
                    ],
                ], JSON_THROW_ON_ERROR),
                'category_id' => $tournament->category->id,
                'tournament_id' => $tournament->id,
            ],
            'president' => [
                'name' => $row['I'],
                'phone' => $row['J'],
                'email' => $row['K'],
            ],
            'coach' => [
                'name' => $row['L'],
                'phone' => $row['M'],
                'email' => $row['N'],
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
                false
            );
        }
        if ($data['coach']['name']) {
            $coach = $this->createOrUpdateUser(
                $formRequest['coach'] ?? null,
                request(),
                'coach',
                'entrenador',
                RegisteredTeamCoach::class,
                false
            );
        }

        $team = Team::create([
            'name' => $data['team']['name'],
            'president_id' => $president?->id,
            'coach_id' => $coach?->id,
            'phone' => $data['team']['phone'] ?? null,
            'email' => $data['team']['email'] ?? null,
            'address' => json_decode($data['team']['address'], false, 512, JSON_THROW_ON_ERROR),
            'colors' => json_decode($data['team']['colors'], false, 512, JSON_THROW_ON_ERROR)
        ]);
        $team->leagues()->attach(auth()->user()->league_id);
        $team->categories()->attach($data['team']['category_id']);
        $team->tournaments()->attach($data['team']['tournament_id']);
    }

    /**
     * @throws \JsonException
     */
    private function normalizeAddress($value)
    {
        $value = trim($value);
        return json_encode([
            'terms' => [
                [
                    'value' => $value,
                    'offset' => 0,
                ],
            ],
            'types' => [
                'establishment',
                'tourist_attraction',
                'point_of_interest',
                'park',
            ],
            'place_id' => null,
            'reference' => null,
            'description' => $value,
            'matched_substrings' => [
                [
                    'length' => strlen($value),
                    'offset' => 0,
                ],
            ],
            'structured_formatting' => [
                'main_text' => $value,
                'secondary_text' => null,
                'main_text_matched_substrings' => [
                    [
                        'length' => strlen($value),
                        'offset' => 0,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    public function search(Request $request): JsonResponse
    {
        $value = $request->get('value', false);
        $teams = Team::with('tournaments.configuration:id:max_players_per_team')
            ->withCount('players')
            ->when($value && $value !== '', fn($query) => $query->where('name', 'like', "%$value%"))
            ->paginate(10, ['*'], 'page', $request->get('page', 1));

        return response()->json($teams);
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
            ->with(['homeTeam', 'homeTeam'])
            ->orWhere('home_team_id', $team->id)
            ->whereIn('status', ['programado', 'aplazado'])
            ->orderBy('match_date')
            ->limit($limit)
            ->get();
        return new NextGamesCollection($nextGames);
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
            'team_id' => $team->id,
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
            ->where('lineup_id', $lineup->id)
            ->where('field_location', $data['field_location'])
            ->first();
        if ($lineupPlayer) {
            $lineupPlayer->field_location = null;
            $lineupPlayer->is_headline = false;
            $lineupPlayer->save();
        }
        $lineupPlayer = LineupPlayer::where('lineup_id', $lineup->id)
            ->where('player_id', $data['player']['player_id'])
            ->update([
                'field_location' => $data['field_location'],
                'is_headline' => true,
            ]);

        return response()->json([
            'message' => 'Jugador agregado a la alineación del partido.',
            'lineup_player' => $lineupPlayer,
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
            $olDLineupPlayer->field_location = null;
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
}
