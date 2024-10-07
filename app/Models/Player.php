<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
