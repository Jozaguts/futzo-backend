<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineupPlayer extends Model
{
    use HasFactory;
    protected $fillable = [
        'lineup_id',
        'player_id',
        'is_headline',
        'field_location',
        'substituted',
        'goals',
        'yellow_card',
        'red_card',
        'doble_yellow_card',
    ];
    public function lineup(): BelongsTo
    {
        return $this->belongsTo(Lineup::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    protected function casts(): array
    {
        return [
            'substituted' => 'boolean',
            'yellow_card' => 'boolean',
            'red_card' => 'boolean',
            'doble_yellow_card' => 'boolean',
        ];
    }
}
