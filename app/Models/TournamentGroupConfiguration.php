<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentGroupConfiguration extends Model
{

    protected $fillable = [
        'tournament_id',
        'teams_per_group',
        'group_sizes',
        'advance_top_n',
        'include_best_thirds',
        'best_thirds_count',
    ];

    protected $casts = [
        'include_best_thirds' => 'boolean',
        'group_sizes' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}

