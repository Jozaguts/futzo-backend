<?php

namespace App\Observers;

use App\Models\Game;

class GameObserver
{
    public function created(Game $game): void
    {

    }

    public function updated(Game $game): void
    {
        if ($game->status === Game::STATUS_COMPLETED) {
            switch (true){
                case  $game->home_goals > $game->away_goals:
                    $game->winner_team_id = $game->home_team_id;
                    break;
                case  $game->away_goals > $game->home_goals:
                    $game->winner_team_id = $game->away_team_id;
                    break;
                case  $game->home_goals === $game->away_goals:
                    $game->winner_team_id = null;
                    break;
            }
            $game->saveQuietly();
            }
    }

    public function deleted(Game $game): void
    {
    }

    public function restored(Game $game): void
    {
    }
}
