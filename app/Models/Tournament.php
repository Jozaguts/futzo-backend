<?php

namespace App\Models;

use App\Observers\TournamentObserver;
use App\Scopes\LeagueScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[ScopedBy(LeagueScope::class)]
#[ObservedBy([[TournamentObserver::class]])]
class Tournament extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, HasSlug;

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
        'football_type_id',
        'league_id',
        'slug',
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',

    ];
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
    protected function startDateToString(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $attributes['start_date'] ? \Carbon\Carbon::parse($attributes['start_date'])->translatedFormat('D d M y') : null,
        );
    }

    protected function endDateToString(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $attributes['end_date'] ? \Carbon\Carbon::parse($attributes['end_date'])->translatedFormat('D d M y') : null,
        );
    }

    public function tournamentPhases(): HasMany|Tournament
    {
        return $this->hasMany(TournamentPhase::class);
    }

    public function phases(): HasManyThrough|Tournament
    {
        return $this->hasManyThrough(
            Phase::class,
            TournamentPhase::class,
            'tournament_id', // FK en tournament_phases
            'id',            // PK en phases
            'id',            // PK en tournaments
            'phase_id'       // FK en tournament_phases
        );
    }
    public function activePhase(): TournamentPhase|Tournament|null
    {
        return $this->tournamentPhases()->where('is_active', 1)->first();
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
        return $this->belongsToMany(Team::class, 'team_tournament')->using(TeamTournament::class);
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
        return $this->belongsToMany(Location::class, LocationTournament::class)
            ->whereNull('location_tournament.deleted_at');
    }

    public function tournamentFields(): HasMany
    {
        return $this->hasMany(TournamentField::class);
    }

    public function fields(): HasManyThrough
    {
        return $this->hasManyThrough(Field::class, TournamentField::class, 'tournament_id', 'id', 'id', 'field_id');
    }

    public function registerMediaCollections(?Media $media = null): void
    {
        $this->addMediaCollection('tournament')
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
    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }
    public function currentRound()
    {
       return Game::select('round')
            ->where('tournament_id', $this->id)
            ->groupBy('round')
           ->havingRaw("SUM(CASE WHEN status != 'completado' THEN 1 ELSE 0 END) > 0")
            ->orderBy('round')
            ->limit(1)
           ->first();
    }
}
