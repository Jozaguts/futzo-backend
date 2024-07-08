<?php

namespace App\Models;

use App\Observers\TournamentObserver;
use App\Scopes\LeagueScope;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Tournament extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;
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
        'tournament_format_id',
        'image',
        'thumbnail'
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
    public function getStartDateAttribute($value): string
    {
        return date('d/m/y', strtotime($value));
    }
    public function getEndDateAttribute($value): string
    {
        return date('d/m/y', strtotime($value));
    }
    protected static function booted(): void
    {
        static::addGlobalScope(new LeagueScope);
        Tournament::observe(TournamentObserver::class);
    }
    protected static function newFactory(): TournamentFactory
    {
        return TournamentFactory::new();
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(TournamentFormat::class, 'tournament_format_id', 'id');
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
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_tournament')
            ->using(LocationTournament::class);
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
