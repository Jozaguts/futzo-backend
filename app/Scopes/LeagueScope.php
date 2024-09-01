<?php

namespace App\Scopes;

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
        if (auth()->check()) {
            if ($model instanceof Team) {
                $builder->join('league_team', 'teams.id', '=', 'league_team.team_id')
                    ->where('league_team.league_id', auth()->user()->league_id);
            } elseif ($model instanceof Tournament) {
                $builder->where('league_id', auth()->user()->league_id);
            }
        }
    }
}
