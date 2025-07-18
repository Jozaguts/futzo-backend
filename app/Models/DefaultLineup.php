<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class DefaultLineup extends Model
{
    use HasFactory;
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }
    public function players(): HasManyThrough
    {
        return $this->hasManyThrough(
            Player::class,
            DefaultLineupPlayer::class,
            'default_lineup_id', // Foreign key on DefaultLineupPlayer
            'id',                // Foreign key on Player
            'id',                // Local key on DefaultLineup
            'player_id'          // Local key on DefaultLineupPlayer
        );
    }
    public function defaultLineupPlayers(): DefaultLineup|HasMany
    {
        return $this->hasMany(DefaultLineupPlayer::class, 'default_lineup_id');
    }

}
