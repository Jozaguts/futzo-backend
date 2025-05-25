<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamTournament extends Pivot
{
    protected $table = 'team_tournament';
//    protected $fillable = ['team_id', 'tournament_id'];
    protected $guarded = [];
}
