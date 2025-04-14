<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HasNotLeagueMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->has('league')) {
            return response()->json([
                'message' => 'User already has a league'
            ], 403);
        }
        return $next($request);
    }
}
