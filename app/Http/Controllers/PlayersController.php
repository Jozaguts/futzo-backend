<?php

namespace App\Http\Controllers;

use App\Events\RegisteredTeamCoach;
use App\Events\RegisteredTeamPresident;
use App\Exports\PlayersTemplateExport;
use App\Http\Requests\PlayerStoreRequest;
use App\Http\Requests\PlayerUpdateRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\TeamStoreRequest;
use App\Http\Resources\PlayerCollection;
use App\Imports\PlayersImport;
use App\Models\Player;
use App\Models\Position;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Builders\PlayerBuilder;
use App\Services\PlayerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlayersController extends Controller
{

    public function index(Request $request)
    {
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
            ->paginate($request->get('per_page', 10), ['*'], 'page', $request->get('page', 1));
        return new PlayerCollection($players);
    }

    public function show($id)
    {

    }

    public function store(PlayerStoreRequest $request, PlayerService $service)
    {
        try {
            $service->store($request);
            return response()->json(['message' => 'Player registered successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(PlayerUpdateRequest $request, $id)
    {
        $request->except('_method');
    }

    public function destroy($id)
    {

    }

    /**
     * @throws \Throwable
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function import(Request $request): \Illuminate\Http\JsonResponse
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
                $this->storePlayerFromRow($row, $team);
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

    private function isValidHeader($header): bool
    {
        $expected = [
            'A' => 'nombre',
            'B' => 'apellido',
            'C' => 'correo',
            'D' => 'teléfono',
            'E' => 'fecha_nacimiento',
            'F' => 'nacionalidad',
            'G' => 'posicion',
            'H' => 'numero',
            'I' => 'altura',
            'J' => 'peso',
            'K' => 'pie_dominante',
            'L' => 'notas_medicas'
        ];
        foreach ($expected as $column => $expectedValue) {
            if (trim($header[$column]) !== $expectedValue) {
                return false;
            }
        }
        return true;
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

        $formRequest = PlayerStoreRequest::create('', 'POST', $data);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));
        $formRequest->validateResolved();
        $builder = new PlayerBuilder;
        $service = new PlayerService($builder);
        $service->store($formRequest);

    }
}
