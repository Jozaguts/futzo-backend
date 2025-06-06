<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultTournamentConfiguration extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'tournament_format_id',
        'football_type_id',
        'max_teams',
        'min_teams',
        'time_between_games',
        'max_players_per_team',
        'min_players_per_team',
        'max_teams_per_player',
        'game_time',
        'game_time',
        'round_trip',
        'group_stage',
        'elimination_round_trip'
    ];
}
