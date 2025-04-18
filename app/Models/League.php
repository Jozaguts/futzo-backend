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
            ->withTimestamps()
            ->with('tags:id,name');
    }

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(Field::class, LeagueField::class)->withPivot('availability');
    }
}
