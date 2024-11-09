<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['game_id', 'team_id', 'player_id', 'goal_time', 'goal_type', 'play_stage', 'goal_schedule', 'goal_half'];

    public function game(): belongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function teams(): hasMany
    {
        return $this->hasMany(Team::class);
    }

    public function players(): hasMany
    {
        return $this->hasMany(Player::class);
    }
}
