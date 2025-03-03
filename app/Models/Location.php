<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Tags\HasTags;

class Location extends Model
{
    use HasFactory, SoftDeletes, HasTags;

    protected $fillable = ['name', 'city', 'address', 'autocomplete_prediction', 'position'];

    protected $casts = [
        'autocomplete_prediction' => 'json',
        'position' => 'json'
    ];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'location_tournament')
            ->using(LocationTournament::class)
            ->withTimestamps();
    }

    public function leagues(): BelongsToMany
    {
        return $this->belongsToMany(League::class, 'league_location');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class);
    }
}
