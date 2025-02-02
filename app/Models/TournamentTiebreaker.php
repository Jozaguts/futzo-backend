<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TournamentTiebreaker extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rule',
        'priority',
        'is_active',
        'tournament_configuration_id',
    ];

    public function tournamentConfiguration(): BelongsTo
    {
        return $this->belongsTo(TournamentConfiguration::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
