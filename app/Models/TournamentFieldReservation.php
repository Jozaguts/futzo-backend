<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentFieldReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'league_field_id',
        'day_of_week',
        'start_minute',
        'end_minute',
        'exclusive',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'exclusive' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function leagueField(): BelongsTo
    {
        return $this->belongsTo(LeagueField::class);
    }
}

