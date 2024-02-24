<?php

namespace App\Http\Controllers;

use App\Http\Requests\PenaltyGoalKeeperStoreRequest;
use App\Http\Requests\PenaltyGoalKeeperUpdateRequest;
class PenaltyGoalKeeperController extends Controller
{

    public function index()
    {

    }
    public function show($id)
    {

    }

    public function store(PenaltyGoalKeeperStoreRequest $request)
    {
        $request->only('game_id','team_id','player_id');
    }

    public function update(PenaltyGoalKeeperUpdateRequest $request,$id)
    {
        $request->only('game_id','team_id','player_id');
    }

    public function destroy($id)
    {

    }
}
