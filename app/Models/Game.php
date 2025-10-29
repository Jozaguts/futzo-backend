<?php

namespace App\Models;

use App\Observers\GameObserver;
use App\Scopes\LeagueScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
#[ObservedBy([GameObserver::class])]
#[ScopedBy(LeagueScope::class)]
class Game extends Model
{
    use HasFactory, SoftDeletes;

    const string STATUS_SCHEDULED = 'programado';
    const string STATUS_IN_PROGRESS = 'en_progreso';
    const string STATUS_COMPLETED = 'completado';
    const string STATUS_POSTPONED = 'aplazado';
    const string STATUS_CANCELED = 'cancelado';

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
        'match_time',
        'starts_at_utc',
        'ends_at_utc',
        'tournament_phase_id',
        'group_key',
        'slug',
        'decided_by_penalties',
        'penalty_home_goals',
        'penalty_away_goals',
        'penalty_winner_team_id',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'match_date' => 'date',
        'match_time' => 'datetime:H:i:s',
        'starts_at_utc' => 'datetime',
        'ends_at_utc' => 'datetime',
        'decided_by_penalties' => 'boolean',
        'penalty_home_goals' => 'integer',
        'penalty_away_goals' => 'integer',
        'penalty_winner_team_id' => 'integer',

    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(function (Game $game) {
                return $game->homeTeam->name . ' vs ' . $game->awayTeam->name . ' - ' . $game->match_date->format('Y-m-d');
            })
            ->saveSlugsTo('slug');
    }

    protected function matchTime(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => \Carbon\Carbon::parse($attributes['match_time'])->format('H:i')
        );
    }

    protected function matchDateToString(): Attribute
    {
        return Attribute::make(
            get: fn(
                $value,
                $attributes
            ) => $attributes['match_date'] ? \Carbon\Carbon::parse($attributes['match_date'])->translatedFormat('D d M y') : null,
        );
    }

    public function winnerTeam(): BelongsTo
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

    public function lineups(): HasMany|Game
    {
        return $this->hasMany(Lineup::class);
    }

    public function homeLineups()
    {
        return $this->hasMany(Lineup::class)->where('team_id', $this->home_team_id);
    }

    public function awayLineups()
    {
        return $this->hasMany(Lineup::class)->where('team_id', $this->away_team_id);
    }

    public function substitutions(): HasMany
    {
        return $this->hasMany(Substitution::class);
    }

    public function gameEvent(): HasMany
    {
        return $this->hasMany(GameEvent::class);
    }
}
