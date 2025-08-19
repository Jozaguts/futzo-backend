<?php

namespace App\Http\Middleware;

use App\Models\Tournament;
use Closure;
use Illuminate\Http\Request;

class CheckTournamentCanRegisterMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tournament = $request->route('tournament');
        if (!$tournament instanceof Tournament) {
            $tournament = Tournament::find((int) $tournament);
        }
        logger('tournament: ' . $tournament);
        if (! ($tournament->teams->count() < $tournament->configuration->max_teams)) {
            return response()->json([
                'message' => "El torneo  {$tournament->name} ha alcanzado el numero maximo de equipos permitidos."
            ], 403);
        }

        return $next($request);
    }
}
