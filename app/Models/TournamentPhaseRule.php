<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPhaseRule extends Model
{

    protected $fillable = [
        'tournament_phase_id',
        'round_trip',
        'away_goals',
        'extra_time',
        'penalties',
        'advance_if_tie',
    ];

    protected $casts = [
        'round_trip' => 'boolean',
        'away_goals' => 'boolean',
        'extra_time' => 'boolean',
        'penalties' => 'boolean',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(TournamentPhase::class, 'tournament_phase_id');
    }
}

