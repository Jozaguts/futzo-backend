<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_SCHEDULED = 'programado';
    const STATUS_IN_PROGRESS = 'en_progreso';
    const STATUS_COMPLETED = 'completado';
    const STATUS_POSTPONED = 'aplazado';
    const STATUS_CANCELED = 'cancelado';

    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'tournament_id',
        'winner_team_id',
        'league_id',
        'status',
        'field_id',
        'location_id',
        'referee_id',
        'round',
        'home_goals',
        'away_goals',
        'match_date',
        'match_time'
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'match_date' => 'date',
        'match_time' => 'datetime:H:i:s',

    ];

    protected function matchTime(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => \Carbon\Carbon::parse($attributes['match_time'])->format('H:i')
        );
    }

    protected function matchDateToString(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $attributes['match_date'] ? \Carbon\Carbon::parse($attributes['match_date'])->translatedFormat('D d M y') : null,
        );
    }

    public function winnerTeam()
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
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

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'game_player')
            ->withPivot('entry_minute', 'exit_minute', 'goals', 'assists')
            ->withTimestamps();
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

    public function tournamentPhase(): BelongsTo
    {
        return $this->belongsTo(TournamentPhase::class, 'tournament_phase_id');
    }
}
