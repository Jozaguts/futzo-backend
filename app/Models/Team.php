<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'group', 'category_id', 'won', 'draw', 'lost', 'goals_against', 'goals_for',
        'goals_difference', 'points','tournament_id','league_id'];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
