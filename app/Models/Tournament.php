<?php

namespace App\Models;

use App\Observers\TournamentObserver;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[ScopedBy(\App\Scopes\LeagueScope::class)]
class Tournament extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'prize',
        'winner',
        'description',
        'status',
        'category_id',
        'tournament_format_id',
        'image',
        'thumbnail',
        'football_type_id'
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function booted(): void
    {
        self::observe(TournamentObserver::class);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(TournamentPhase::class, 'tournament_id', 'id');
    }

    public function configuration(): HasOne
    {
        return $this->hasOne(TournamentConfiguration::class);
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(TournamentFormat::class, 'tournament_format_id', 'id');
    }

    public function footballType(): BelongsTo
    {
        return $this->belongsTo(FootballType::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->using(TeamTournament::class);
    }

    public function players(): HasManyThrough
    {
        return $this->hasManyThrough(Player::class, TeamTournament::class, 'tournament_id', 'team_id', 'id', 'team_id');
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

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_tournament')
            ->using(LocationTournament::class)
            ->whereNull('location_tournament.deleted_at');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MatchSchedule::class);
    }

    public function registerMediaCollections(?Media $media = null): void
    {
        $this->addMediaCollection('tournament')
            ->singleFile()
            ->storeConversionsOnDisk('s3')
            ->registerMediaConversions(function (Media $media = null) {
                $this->addMediaConversion('thumbnail')
                    ->width(150)
                    ->height(150);
                $this->addMediaConversion('default')
                    ->width(400)
                    ->height(400);
            });
    }
}
