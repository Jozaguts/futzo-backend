<?php

namespace App\Actions\Tournament;

use App\Actions\BuildTournamentScheduleAction;
use App\Http\Resources\LastGamesCollection;
use App\Http\Resources\NextGamesCollection;
use App\Models\Tournament;

class GetTournamentPublicDetailsAction {
    public function execute(Tournament $tournament)
    {
        $header = [
            'name' => $tournament->league->name,
            'phase' => $tournament->activePhase()->name,
            'startDate' => $tournament->start_date->format('d M Y'),
            'location' => '',
            'teams' => $tournament->teams()->count(),
            'status' => $tournament->status,
            'format' => $tournament->format->name,
        ];
        $standings = app(GetTournamentStandingsAction::class)->execute($tournament);
        $lastGames = app(GetTournamentLastResultAction::class)->execute($tournament);
        $stats = app(GetTournamentStatsAction::class)->execute($tournament);
        $upcomingMatches = app(GetNextTournamentGamesAction::class)->execute($tournament);
        $schedule = app(BuildTournamentScheduleAction::class)->execute($tournament, request(), 1);
        return [
            'header' => $header,
            'lastResults' => new LastGamesCollection($lastGames),
            'standings' => $standings,
            'stats' => $stats,
            'upcomingMatches' => new NextGamesCollection($upcomingMatches),
            'schedule' => $schedule
        ];
    }
}