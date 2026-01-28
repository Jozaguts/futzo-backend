<?php

namespace App\Actions\Tournament;

use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Collection;
use LaravelIdea\Helper\App\Models\_IH_Game_C;

class GetNextTournamentGamesAction {
    public function execute(Tournament $tournament, int $limit = 3): Collection|array|_IH_Game_C
    {
       return  $tournament->games()
            ->with(['homeTeam:id,name,image', 'awayTeam:id,name,image','location:id,name','field:id,name'])
            ->where('status', Game::STATUS_SCHEDULED)
            ->orderBy('match_date', 'desc')
            ->orderBy('match_time', 'desc')
            ->limit($limit)
            ->get();
    }

}