<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HasNotLeagueMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!empty($request->user()->league_id)) {
            return response()->json([
                'message' => "El usuario ya tiene una liga registrada  \"{$request->user()->league->name}\"",
            ], 403);
        }
        return $next($request);
    }
}
