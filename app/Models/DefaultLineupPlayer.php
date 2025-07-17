<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefaultLineupPlayer extends Model
{
    use HasFactory;

    public function defaultLineup(): BelongsTo
    {
        return $this->belongsTo(DefaultLineup::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
