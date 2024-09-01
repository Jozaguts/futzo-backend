<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class LeagueTeam extends Pivot
{
    use HasFactory;
    protected $fillable = ['league_id', 'team_id'];
    
}
