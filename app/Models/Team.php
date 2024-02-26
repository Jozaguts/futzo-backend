<?php

namespace App\Models;

use App\Scopes\LeagueScope;
use App\Traits\HasLeague;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes, HasLeague;
    protected $fillable = [
        'name',
        'tournament_id',
        'category_id',
        'league_id',
        'president_name',
        'coach_name',
        'phone',
        'email',
        'address',
        'image',
    ];


    protected static function booted(): void
    {
        static::addGlobalScope(new LeagueScope);

    }
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    public function colors ()
    {
        return $this->hasOne(TeamDetail::class);
    }
}
