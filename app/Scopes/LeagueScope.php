<?php

namespace App\Scopes;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class LeagueScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Determine effective league id.
        // If authenticated, always trust the user record, not client headers.
        $leagueIdFromAuth = auth()->check() ? auth()->user()->league_id : null;
        $leagueIdFromHeader = request()->header('X-League-ID');

        // When authenticated, ignore header entirely.
        // When not authenticated, allow header to filter public listings.
        $effectiveLeagueId = auth()->check() ? $leagueIdFromAuth : $leagueIdFromHeader;

        // If authenticated but without league, restrict to no results for scoped models.
        if (auth()->check() && is_null($effectiveLeagueId)) {
            $builder->whereRaw('1 = 0');
            return;
        }

        // If we still don't have a league id (unauthenticated public call without header), skip scoping.
        if (is_null($effectiveLeagueId)) {
            return;
        }

        if ($model instanceof Team) {
            $teamIds = \DB::table('league_team')
                ->where('league_id', $effectiveLeagueId)
                ->pluck('team_id');
            $builder->whereIn('teams.id', $teamIds);
        } elseif ($model instanceof Tournament) {
            $builder->where('league_id', $effectiveLeagueId);
        } elseif ($model instanceof Game) {
            $builder->where('league_id', $effectiveLeagueId);
        } elseif ($model instanceof Player) {
            $builder->whereHas('team', function ($query) use ($effectiveLeagueId) {
                $query->whereIn('teams.id', function ($query) use ($effectiveLeagueId) {
                    $query->select('team_id')
                        ->from('league_team')
                        ->where('league_id', $effectiveLeagueId);
                });
            });
        }
    }
}
