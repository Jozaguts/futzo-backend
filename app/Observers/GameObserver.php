<?php

namespace App\Observers;

use App\Jobs\RecalculateStandingsJob;
use App\Models\Game;
use App\Services\StandingsService;
use Illuminate\Support\Facades\DB;
use Throwable;

class GameObserver
{
    /**
     * @throws Throwable
     */
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

            if(app()->runningInConsole()){
                app(StandingsService::class)->recalculateStandingsForPhase(
                    $game->tournament_id,
                    $game->tournament_phase_id,
                    $game->id,
                );
            }else {
                RecalculateStandingsJob::dispatch(
                    $game->tournament_id,
                    $game->tournament_phase_id,
                    $game->id,
                )->onQueue('standings');
            }
        }
    }

    public function saved(Game $game): void
    {
        $groupKey = $game->group_key;

        if (!$groupKey) {
            return;
        }

        $teamIds = array_filter([
            $game->home_team_id,
            $game->away_team_id,
        ]);

        if (empty($teamIds)) {
            return;
        }

        $assignments = DB::table('team_tournament')
            ->where('tournament_id', $game->tournament_id)
            ->whereIn('team_id', $teamIds)
            ->pluck('group_key', 'team_id');

        foreach ($teamIds as $teamId) {
            $current = $assignments[$teamId] ?? null;

            if ($current === $groupKey || ($current !== null && $current !== $groupKey)) {
                continue;
            }

            DB::table('team_tournament')
                ->where('tournament_id', $game->tournament_id)
                ->where('team_id', $teamId)
                ->update([
                    'group_key' => $groupKey,
                    'updated_at' => now(),
                ]);
        }
    }
}
