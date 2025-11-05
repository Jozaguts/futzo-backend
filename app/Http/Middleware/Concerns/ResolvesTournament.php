<?php

namespace App\Http\Middleware\Concerns;

use App\Models\Tournament;
use Illuminate\Http\Request;

trait ResolvesTournament
{
    protected function resolveTournament(Request $request): ?Tournament
    {
        $routeParam = $request->route('tournament');
        if ($routeParam instanceof Tournament) {
            return $routeParam;
        }

        if (is_numeric($routeParam)) {
            return Tournament::find((int) $routeParam);
        }

        if (is_string($routeParam) && $routeParam !== '') {
            return Tournament::where('slug', $routeParam)->first();
        }

        $tournamentId = $request->input('team.tournament_id')
            ?? $request->input('tournament_id');

        if ($tournamentId) {
            return Tournament::find((int) $tournamentId);
        }

        return null;
    }
}
