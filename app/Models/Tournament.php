<?php

namespace App\Models;

use App\Observers\TournamentObserver;
use App\Scopes\LeagueScope;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tournament extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'league_id',
        'name',
        'start_date',
        'end_date',
        'prize',
        'winner',
        'description',
        'status',
        'category_id',
        'tournament_format_id'
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
    protected static function booted(): void
    {
        static::addGlobalScope(new LeagueScope);
        Tournament::observe(TournamentObserver::class);
    }
    protected static function newFactory(): TournamentFactory
    {
        return TournamentFactory::new();
    }

    public function TournamentFormat(): BelongsTo
    {
        return $this->belongsTo(TournamentFormat::class);
    }
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function players():HasManyThrough
    {
        return $this->hasManyThrough(Player::class, Team::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
