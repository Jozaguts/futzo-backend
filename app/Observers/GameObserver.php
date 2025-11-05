<?php

namespace App\Observers;

use App\Jobs\RecalculateStandingsJob;
use App\Models\Game;
use App\Services\StandingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GameObserver
{
    /**
     * @throws Throwable
     */
    public function updated(Game $game): void
    {
        $originalStatus = $game->getOriginal('status');
        Log::info('GameObserver::updated', [
            'game_id' => $game->id,
            'original_status' => $originalStatus,
            'current_status' => $game->status,
            'home_goals' => $game->home_goals,
            'away_goals' => $game->away_goals,
            'decided_by_penalties' => $game->decided_by_penalties,
            'penalty_winner_team_id' => $game->penalty_winner_team_id,
        ]);

        if ($game->status === Game::STATUS_COMPLETED) {
            $winnerTeamId = null;

            if ($game->decided_by_penalties && $game->penalty_winner_team_id) {
                $winnerTeamId = $game->penalty_winner_team_id;
            } else {
                $winnerTeamId = match (true) {
                    $game->home_goals > $game->away_goals => $game->home_team_id,
                    $game->away_goals > $game->home_goals => $game->away_team_id,
                    default => null,
                };
            }

            if ($game->winner_team_id !== $winnerTeamId) {
                Log::info('GameObserver::updated winner adjustment', [
                    'game_id' => $game->id,
                    'previous_winner_team_id' => $game->winner_team_id,
                    'resolved_winner_team_id' => $winnerTeamId,
                ]);
                $game->winner_team_id = $winnerTeamId;
                $game->saveQuietly();
            }

            $this->dispatchStandingsRecalculation($game);
            return;
        }

        if ($originalStatus === Game::STATUS_COMPLETED && $game->status !== Game::STATUS_COMPLETED) {
            if (!is_null($game->winner_team_id)) {
                Log::info('GameObserver::updated clearing winner due to status change', [
                    'game_id' => $game->id,
                    'new_status' => $game->status,
                ]);
                $game->winner_team_id = null;
                $game->saveQuietly();
            }

            $this->dispatchStandingsRecalculation($game);
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

            Log::info('GameObserver::saved updated team group_key', [
                'game_id' => $game->id,
                'team_id' => $teamId,
                'new_group_key' => $groupKey,
            ]);
        }
    }

    /**
     * @throws Throwable
     */
    protected function dispatchStandingsRecalculation(Game $game): void
    {
        Log::info('GameObserver::dispatchStandingsRecalculation', [
            'game_id' => $game->id,
            'tournament_id' => $game->tournament_id,
            'tournament_phase_id' => $game->tournament_phase_id,
            'queue' => app()->runningInConsole() ? 'sync' : 'standings',
        ]);

        if (app()->runningInConsole()) {
            app(StandingsService::class)->recalculateStandingsForPhase(
                $game->tournament_id,
                $game->tournament_phase_id,
                $game->id,
            );
            return;
        }

        RecalculateStandingsJob::dispatch(
            $game->tournament_id,
            $game->tournament_phase_id,
            $game->id,
        )->onQueue('standings');
    }
}
