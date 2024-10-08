<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamTournament extends Pivot
{
    use HasFactory;
    protected $fillable =['team_id', 'tournament_id'];
}
