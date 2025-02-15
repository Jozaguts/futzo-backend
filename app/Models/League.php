<?php

namespace App\Models;

use Database\Factories\LeagueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class League extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'creation_date',
        'logo',
        'banner',
        'status',
        'location',
        'football_type_id'
    ];
    protected $casts = [
        'creation_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function newFactory(): LeagueFactory
    {
        return LeagueFactory::new();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'league_team');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, LeagueLocation::class)
            ->withPivot('availability');
    }

    public function getLocationsAttribute()
    {
        return $this->locations()->get()->map(function ($location) {
            if (is_null($location->pivot->availability)) {
                $location->pivot->availability = [];
            } else {
                $location->pivot->availability = json_decode($location->pivot->availability, true, 512, JSON_THROW_ON_ERROR);
            }
            return $location;
        });
    }
}
