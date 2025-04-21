<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatchSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tournament_id',
        'home_team_id',
        'away_team_id',
        'match_date',
        'match_time',
        'location_id',
        'referee_id',
        'field_id',
        'status',
        'result',
        'round'
    ];
    protected $casts = [
        'match_date' => 'date',
        'match_time' => 'datetime:H:i:s',
    ];

    protected function matchDateToString(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $attributes['match_date'] ? \Carbon\Carbon::parse($attributes['match_date'])->translatedFormat('D d M y') : null,
        );
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}
