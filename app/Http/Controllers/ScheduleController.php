<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
   public function generate(Request $request)
   {
       $tournament = Tournament::where('id', $request->tournament_id)
           ->where('league_id', $request->league_id)
           ->firstOrFail();

       $this->authorize('generateTournamentSchedule', $tournament);

       return response()->json([], 201);
   }
}
