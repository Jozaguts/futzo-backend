<?php

namespace App\Http\Controllers;

use App\Exports\PlayersTemplateExport;
use App\Http\Requests\PlayerStoreRequest;
use App\Http\Requests\PlayerUpdateRequest;
use App\Http\Resources\PlayerCollection;
use App\Imports\PlayersImport;
use App\Models\Player;
use App\Services\PlayerService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
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

    public function import(Request $request): \Illuminate\Http\JsonResponse
    {
        Excel::import(new PlayersImport(), $request->file('file'));
        return response()->json('File imported successfully');
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function downloadPlayersTemplate(): BinaryFileResponse
    {
        return Excel::download(new PlayersTemplateExport(), 'jugadores_template.xlsx');
    }
}
