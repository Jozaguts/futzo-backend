<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class CanMakeSupportMessageRequestMiddleware{
    public function handle(Request $request, Closure $next)
    {
        if ( $request->user()->tickets()->where('status', 'open')->count() > 0) {
            return response()->json([
                'message' => 'Tiene un ticket abierto. Por favor, espere a que se resuelva antes de crear uno nuevo.'
            ], 403);
        }
        return $next($request);
    }
}
