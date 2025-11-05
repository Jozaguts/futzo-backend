<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ResolvesTournament;
use App\Models\Tournament;
use Closure;
use Illuminate\Http\Request;

class CheckTournamentEliminationPhaseMiddleware
{
    use ResolvesTournament;

    private const ELIMINATION_PHASES = [
        'Dieciseisavos de Final',
        'Octavos de Final',
        'Cuartos de Final',
        'Semifinales',
        'Final',
    ];

    public function handle(Request $request, Closure $next)
    {
        $tournament = $this->resolveTournament($request);

        if (! $tournament instanceof Tournament) {
            return response()->json([
                'message' => 'No se encontró el torneo solicitado.',
            ], 404);
        }

        $activePhase = $tournament->activePhase();
        $phaseName = $activePhase?->phase?->name;

        if ($phaseName && in_array($phaseName, self::ELIMINATION_PHASES, true)) {
            return response()->json([
                'message' => "El torneo {$tournament->name} se encuentra en {$phaseName}, ya no se permiten nuevos equipos.",
            ], 403);
        }

        return $next($request);
    }
}
