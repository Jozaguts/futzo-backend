<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy(\App\Scopes\LeagueScope::class)]
class Team extends Model
{
    use HasFactory, SoftDeletes;
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
    ];
    protected $casts = [
        'address' => 'array',
        'colors' => 'array'
    ];
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
    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class)->using(TeamTournament::class);
    }
    public function categories()
    {
        return $this->belongsToMany(Category::class)->using(CategoryTeam::class);
    }
}
