<?php

namespace App\Http\Controllers;

use App\Http\Resources\NextGamesCollection;
use App\Models\Game;
use App\Services\DashboardStatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $range = $request->query('range');

        if (!in_array($range, ['last24Hrs', 'lastWeek', 'lastMonth', 'lastYear'])) {
            return response()->json(['error' => 'Invalid range specified'], 400);
        }

        $stats = DashboardStatsService::getTeamStats($range);

        return response()->json($stats);

    }

    public function nextGames(Request $request): NextGamesCollection
    {
        $nowDate = now()->toDateString();
        $nowTime = now()->toTimeString();
        $nextGames = Game::where('status', Game::STATUS_SCHEDULED)
            ->where(function ($query) use ($nowDate, $nowTime) {
                $query->where('match_date', '>', $nowDate)
                    ->orWhere(function ($q) use ($nowDate, $nowTime) {
                        $q->where('match_date', $nowDate)
                            ->where('match_time', '>=', $nowTime);
                    });
            })
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->take(3)
            ->get();

        return new NextGamesCollection($nextGames);
    }
}
