<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lineup extends Model
{
    use  SoftDeletes;
    protected $fillable = ['game_id', 'player_id', 'first_team_player','round', 'formation_id','default_lineup_id','team_id'];
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
    public function defaultLineup(): BelongsTo
    {
        return $this->belongsTo(DefaultLineup::class);
    }

    public function players(): HasMany|Lineup
    {
        return $this->hasMany(LineupPlayer::class);
    }
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
