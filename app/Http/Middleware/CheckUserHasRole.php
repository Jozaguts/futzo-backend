<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserHasRole
{

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->roles->isEmpty()) {
            abort(403, 'Have no assigned role.');
        }

        return $next($request);
    }
}
