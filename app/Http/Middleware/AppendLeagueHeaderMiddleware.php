<?php

namespace App\Http\Middleware;

use App\Models\League;
use App\Models\Tournament;
use Closure;
use Illuminate\Http\Request;

class AppendLeagueHeaderMiddleware
{
    public function handle(Request $request, Closure $next)
    {

        $response = $next($request);
        $leagueId = $response->headers->get('X-League-Id');

        if (! $leagueId){
            if (auth()->check()){
                $leagueId = auth()->user()->league_id;
            }
            if ($request->route('league') instanceof  League ){
                $leagueId = $request->route('league')->id;
            }
            if ($request->route('tournament') instanceof  Tournament ){
                $leagueId = $request->route('tournament')->league_id;
            }
        }


        if ($leagueId) {
            $response->headers->set('X-League-Id', $leagueId);
        }
        return $response;
    }
}
