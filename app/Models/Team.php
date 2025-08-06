<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
#[ScopedBy(\App\Scopes\LeagueScope::class)]
class Team extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, HasSlug;

    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'description',
        'image',
        'president_id',
        'coach_id',
        'colors',
        'slug'
    ];
    protected $casts = [
        'address' => 'array',
        'colors' => 'array'
    ];
    protected $appends = ['rgba_color'];

    protected static function booted(): void
    {
        static::creating(static function ($team) {
            $team->colors = $team->colors ?? config('constants.colors');
        });
    }

    protected function image(): Attribute
    {
        $color = '000';
        $background = 'fff';
        if (isset($this->colors['home']['primary'])) {
            $color = $this->colors['home']['primary'] === '#fff' ? '000' : 'fff';
            $background = str_replace('#', '', $this->colors['home']['primary']);
        }

        return Attribute::make(
            get: fn($value
            ) => $value ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=' . $color . '&background=' . $background
        );
    }

    protected function rgbaColor(): Attribute
    {
        $color = $this->colos['home']['primary'] ?? '#fff';
        return Attribute::make(
            get: static fn($value) => hex2rgb($color)
        );
    }


    public function defaultLineup(): HasOne
    {
        return $this->hasOne(DefaultLineup::class);
    }

    public function lineup(): HasOne
    {
        return $this->hasOne(Lineup::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function president(): BelongsTo
    {
        return $this->belongsTo(User::class, 'president_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function leagues(): BelongsToMany
    {
        return $this->belongsToMany(League::class, 'league_team');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'team_id');
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'team_tournament')->using(TeamTournament::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->using(CategoryTeam::class);
    }

    public function category(): Model|Category|null
    {
        return $this->categories()->first();
    }

    public function registerMediaCollections(?Media $media = null): void
    {
        $this->addMediaCollection('team')
            ->singleFile()
            ->storeConversionsOnDisk('s3')
            ->registerMediaConversions(function (?Media $media) {
                $this->addMediaConversion('thumbnail')
                    ->width(150)
                    ->height(150);
                $this->addMediaConversion('default')
                    ->width(400)
                    ->height(400);
            });
    }

    public function teamEvents(): HasMany
    {
        return $this->hasMany(GameEvent::class);
    }

    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }

    public function games(): Builder
    {
        return Game::query()->where(function ($query) {
            $query->where('home_team_id', $this->id)
                ->orWhere('away_team_id', $this->id);
        });
    }
}
