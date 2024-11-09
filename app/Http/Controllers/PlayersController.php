<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerStoreRequest;
use App\Http\Requests\PlayerUpdateRequest;
use App\Models\Player;
use App\Services\PlayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayersController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        return response()->json(Player::with(['user:id,name,last_name'])->get());
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


}
