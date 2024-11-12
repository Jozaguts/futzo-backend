<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        // Obtener el rango de la consulta (p.ej., 'last24Hrs', 'lastWeek', 'lastMonth', 'lastYear')
        $range = $request->query('range');

        // Validar que el rango sea válido
        if (!in_array($range, ['last24Hrs', 'lastWeek', 'lastMonth', 'lastYear'])) {
            return response()->json(['error' => 'Invalid range specified'], 400);
        }

        // Obtener las estadísticas para el rango especificado
        $stats = DashboardStatsService::getTeamStats($range);

        return response()->json($stats);

    }
}
