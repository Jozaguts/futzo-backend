<?php

namespace App\Http\Middleware;

use App\Models\League;
use Closure;
use Illuminate\Http\Request;

class AppendLeagueHeaderMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $leagueId = $response->headers->get('X-League-Id');

        if (! $leagueId && auth()->check()){
            $leagueId = auth()->user()->league_id;
        }

        if(!$leagueId && $request->route('league')){
            $leagueId = $request->route('league') instanceof  League
                ? $request->route('league')->id
                : null;
        }
        if ($leagueId) {
            $response->headers->set('X-League-Id', $leagueId);
        }
        return $response;
    }
}
