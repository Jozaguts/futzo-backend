<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Game;

class GameController extends Controller
{
    public function show(Game $game): GameResource
    {
        return new GameResource($game);
    }

    public function update(Game $game)
    {
        return response()->json(['message' => 'Game updated successfully']);
    }
}
