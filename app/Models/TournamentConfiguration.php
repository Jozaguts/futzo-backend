<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentConfiguration extends Model
{
    protected $fillable = [
        'tournament_id',
        'tournament_format_id',
        'football_type_id',
        'max_teams',
        'min_teams',
        'time_between_games',
        'max_players_per_team',
        'min_players_per_team',
        'max_teams_per_player',
        'game_time',
        'round_trip',
        'group_stage',
    ];
    protected $casts = [
        'round_trip' => 'boolean',
        'group_stage' => 'boolean',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function tiebreakers(): HasMany
    {
        return $this->hasMany(TournamentTiebreaker::class);
    }
}
