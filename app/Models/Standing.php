<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Standing extends Model
{
    protected $fillable = [
        'team_id',
        'team_tournament_id',
        'updated_after_game_id',
        'tournament_phase_id',
        'matches_played',
        'tournament_id',
        'league_id',
        'wins',
        'draws',
        'losses',
        'goals_for',
        'goals_against',
        'goal_difference',
        'points',
        'fair_play_points',
        'last_5',
        'rank',
    ];
}
