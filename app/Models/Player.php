<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): PlayerFactory
    {
        return PlayerFactory::new();
    }

    protected $fillable = [
        'user_id',
        'team_id',
        'position_id',
        'category_id',
        'birthday',
        'height',
        'weight',
        'dominant_foot',
        'nationality',
        'medical_notes',
        'number',
        'birthdate'
    ];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(GoalDetail::class);
    }

    public function image()
    {
        return $this->user ? $this->user->image : null;
    }

}
