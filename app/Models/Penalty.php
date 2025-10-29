<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penalty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['game_id', 'team_id', 'player_id', 'score_goal', 'kicks_number'];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
