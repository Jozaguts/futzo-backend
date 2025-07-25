<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameEvent extends Model
{

    const string SUBSTITUTION =  'substitution';
    protected $fillable = [
        'game_id',
        'player_id',
        'related_player_id',
        'team_id',
        'minute',
        'type',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function relatedPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'related_player_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
