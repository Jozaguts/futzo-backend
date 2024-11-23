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
                $teamIds = \DB::table('league_team')
                    ->where('league_id', auth()->user()->league_id)
                    ->pluck('team_id');

                $builder->whereIn('teams.id', $teamIds);
            } elseif ($model instanceof Tournament) {
                $builder->where('league_id', auth()->user()->league_id);
            }
        }
    }
}
