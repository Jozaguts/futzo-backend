<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTeamCanRegisterMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $team = $request->route('team');

        if (! ($team->players->count() < $team->tournaments()->first()->configuration->max_players_per_team)) {
            return response()->json([
                'message' => "El Equipo  {$team->name} ha alcanzado el numero maximo de jugadores permitidos."
            ], 403);
        }


        return $next($request);
    }
}
