<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\ScheduleGeneratorService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function generate(Request $request, ScheduleGeneratorService $scheduleGeneratorService)
    {
        $tournament = Tournament::where('id', $request->tournament_id)
            ->where('league_id', $request->league_id)
            ->firstOrFail();

        $matches = $scheduleGeneratorService->generateFor($tournament);

        return response()->json(['matches' => $matches], 201);
    }
}
