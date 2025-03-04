<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Field extends Model
{
    use HasFactory, SoftDeletes;

    public const defaultType = 'Fútbol 11';
    public const defaultDimensions = ['length' => 120, 'width' => 90];
    public $with = ['leaguesFields'];
    protected $fillable = [
        'location_id',
        'name',
        'type',
        'dimensions',
    ];

    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function leaguesFields(): HasMany|Field
    {
        return $this->hasMany(LeagueField::class, 'field_id');
    }

    public function tournamentsFields(): HasMany|Field
    {
        return $this->hasMany(TournamentField::class, 'field_id');
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_fields')->withPivot('availability');
    }
}
