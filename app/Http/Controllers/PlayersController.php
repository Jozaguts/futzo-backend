<?php

namespace App\Http\Controllers;

use App\Exports\PlayersTemplateExport;
use App\Http\Requests\PlayerStoreRequest;
use App\Http\Requests\PlayerUpdateRequest;
use App\Http\Resources\PlayerCollection;
use App\Http\Resources\PlayerResource;
use App\Models\GameEvent;
use App\Models\Player;
use App\Models\Position;
use App\Models\Team;
use App\Services\Builders\PlayerBuilder;
use App\Services\PlayerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlayersController extends Controller
{

    public function index(Request $request): PlayerCollection
    {
        $search = $request->query('search', false);
        $players = Player::select([
            'id',
            'user_id',
            'team_id',
            'position_id',
            'category_id',
            'number',
            'birthdate',
            'height',
            'nationality',
            'weight'
        ])
            ->with(['team:teams.id,teams.name', 'position', 'category:id,name'])
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function($query) use ($search) {
                    $query->where('name', 'LIKE', "%{$search}%");
                });
            })
            ->paginate($request->get('per_page', 10), ['*'], 'page', $request->get('page', 1));
        return new PlayerCollection($players);
    }

    public function show(Player $player): PlayerResource
    {
        $player->loadMissing([
            'user',
            'team.tournaments.category',
            'position',
            'category',
        ]);

        $tournaments = $this->collectPlayerTournaments($player);
        $teams = $this->collectPlayerTeams($player, $tournaments);
        $stats = $this->buildPlayerStats($player, count($tournaments));

        $player->setAttribute('tournaments_payload', $tournaments);
        $player->setAttribute('teams_payload', $teams);
        $player->setAttribute('stats_payload', $stats);

        return new PlayerResource($player);
    }

    /**
     * @throws \Throwable
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function store(PlayerStoreRequest $request, Team $team, PlayerService $service): ?JsonResponse
    {
        try {
            $service->store($request->userFormData(), $request->playerFormData());
            return response()->json(['message' => 'Player registered successfully'], 201);
        } catch (Exception $e) {
            logger($e->getMessage());
            return response()->json(['error' => 'Error al procesar el registro del jugador, por favor comuníquese con el administrador'], 500);
        }
    }

    public function update(PlayerUpdateRequest $request, Player $player): PlayerResource
    {
        $data = $request->validated();
        $userPayload = Arr::only($data, ['name', 'last_name', 'email', 'phone']);
        $playerPayload = Arr::only($data, [
            'birthdate',
            'nationality',
            'position_id',
            'number',
            'height',
            'weight',
            'dominant_foot',
            'medical_notes',
            'notes',
        ]);

        if (!empty($userPayload) && $player->user) {
            $player->user->fill($userPayload);
            $player->user->save();
        }

        if (array_key_exists('position_id', $playerPayload)) {
            $positionId = $playerPayload['position_id'];
            $player->position()->associate($positionId ? Position::find($positionId) : null);
        }

        $player->fill(Arr::except($playerPayload, ['position_id']));
        $player->save();

        $player->loadMissing([
            'user',
            'team.tournaments.category',
            'position',
            'category',
        ]);

        $tournaments = $this->collectPlayerTournaments($player);
        $teams = $this->collectPlayerTeams($player, $tournaments);
        $stats = $this->buildPlayerStats($player, count($tournaments));

        $player->setAttribute('tournaments_payload', $tournaments);
        $player->setAttribute('teams_payload', $teams);
        $player->setAttribute('stats_payload', $stats);

        return new PlayerResource($player);
    }

    /**
     * @throws \Throwable
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $spreadsheet = IOFactory::load($request->file('file'));
            $team = Team::find($request->get('team_id'));
            $sheetNames = $spreadsheet->getSheetNames();
            $found = false;
            $playersData = [];
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
                    $playersData = $rows;
                    break;
                }
            }
            if (!$found) {
                return response()->json([
                    'message' => 'No se encontró una hoja de datos válida. Asegúrese de que las columnas coincidan con el formato requerido.',
                ], 422);
            }
            foreach ($playersData as $row) {
                $nameIsEmpty = empty($row['A']);
                $lastNameIsEmpty = empty($row['B']);

                if (!$nameIsEmpty && !$lastNameIsEmpty){
                    $this->storePlayerFromRow($row, $team);
                }
            }
            return response()->json('File imported successfully');
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function downloadPlayersTemplate(): BinaryFileResponse
    {
        return Excel::download(new PlayersTemplateExport(), 'jugadores_template.xlsx');
    }

    private function collectPlayerTournaments(Player $player): array
    {
        if (!$player->team) {
            return [];
        }

        return $player->team->tournaments
            ->unique('id')
            ->map(static function ($tournament) {
                return [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'status' => $tournament->status,
                    'image' => $tournament->image,
                    'slug' => $tournament->slug,
                    'start_date' => $tournament->start_date?->toDateString(),
                    'start_date_label' => $tournament->start_date_to_string,
                    'category' => $tournament->category ? [
                        'id' => $tournament->category->id,
                        'name' => $tournament->category->name,
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    private function collectPlayerTeams(Player $player, array $tournaments): array
    {
        if (!$player->team) {
            return [];
        }

        $category = $player->category;

        return [[
            'id' => $player->team->id,
            'name' => $player->team->name,
            'slug' => $player->team->slug,
            'image' => $player->team->image,
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
            ] : null,
            'tournament' => $tournaments[0] ?? null,
        ]];
    }

    private function buildPlayerStats(Player $player, int $tournamentsCount): array
    {
        $eventTotals = $player->gameEvents()
            ->select('type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $gamesPlayed = $this->countGamesPlayed($player);

        return [
            'games_played' => $gamesPlayed,
            'games' => $gamesPlayed,
            'tournaments' => $tournamentsCount,
            'tournaments_played' => $tournamentsCount,
            'goals' => ($eventTotals[GameEvent::GOAL] ?? 0) + ($eventTotals[GameEvent::PENALTY] ?? 0),
            'assists' => $eventTotals['assist'] ?? 0,
            'fouls' => $eventTotals['foul'] ?? 0,
            'fouls_committed' => $eventTotals['foul'] ?? 0,
            'yellow_cards' => $eventTotals[GameEvent::YELLOW_CARD] ?? 0,
            'red_cards' => $eventTotals[GameEvent::RED_CARD] ?? 0,
            'own_goals' => $eventTotals[GameEvent::OWN_GOAL] ?? 0,
            'minutes_played' => 0,
            'clean_sheets' => 0,
        ];
    }

    private function countGamesPlayed(Player $player): int
    {
        $gamesPlayed = $player->lineupPlayers()
            ->select('lineups.game_id')
            ->join('lineups', 'lineup_players.lineup_id', '=', 'lineups.id')
            ->whereNull('lineups.deleted_at')
            ->whereNotNull('lineups.game_id')
            ->distinct()
            ->count('lineups.game_id');

        if ($gamesPlayed > 0) {
            return $gamesPlayed;
        }

        return $player->gameEvents()
            ->distinct('game_id')
            ->count('game_id');
    }

    private function isValidHeader($header): bool
    {
        $expected = [
            'A' => 'nombre',
            'B' => 'apellido',
            'C' => 'correo',
            'D' => 'teléfono',
            'E' => 'fecha_nacimiento',
            'F' => 'nacionalidad',
            'G' => 'posición',
            'H' => 'numero',
            'I' => 'altura',
            'J' => 'peso',
            'K' => 'pie_dominante',
            'L' => 'notas_medicas'
        ];
        return array_all($expected, static fn($expectedValue, $column) => trim($header[$column]) === $expectedValue);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws \Throwable
     */
    private function storePlayerFromRow($row, Team $team): void
    {
        $position_id = Position::whereLike('name', $row['G'])->first()?->id;
        $data = [
            'basic' => [
                'name' => $row['A'],
                'last_name' => $row['B'],
                'birthdate' => Carbon::create($row['E'])?->toDateString(),
                'nationality' => $row['F'],
                'team_id' => $team?->id,
                'category_id' => $team->category()->id,
            ],
            'details' => [
                'position_id' => $position_id,
                'number' => $row['H'],
                'height' => $row['I'],
                'weight' => $row['J'],
                'dominant_foot' => $row['K'],
                'medical_notes' => $row['L'],
            ],
            'contact' => [
                'email' => $row['C'],
                'phone' => $row['D'],
                'notes' => $row['L'],
            ]
        ];
        $userData = [
            'name' => $data['basic']['name'],
            'last_name' => $data['basic']['last_name'],
            'email' => $data['contact']['email'],
            'phone' => $data['contact']['phone'],
        ];
        if(isset($data['basic']['image'])){
            $userData['image'] = $data['basic']['image'];
        }
        $playerData = [
            'birthdate' => $data['basic']['birthdate'],
            'team_id' => $data['basic']['team_id'],
            'category_id' => $data['basic']['category_id'],
            'nationality' => $data['basic']['nationality'],
            'position_id' => $data['details']['position_id'],
            'number' => $data['details']['number'],
            'height' => $data['details']['height'],
            'weight' => $data['details']['weight'],
            'dominant_foot' => $data['details']['dominant_foot'],
            'medical_notes' => $data['details']['medical_notes'],
        ];
        $service = new PlayerService(new PlayerBuilder);
        $service->store($userData, $playerData);

    }
}
