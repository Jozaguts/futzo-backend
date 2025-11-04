<?php

namespace App\Models;

use App\Scopes\LeagueScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy(LeagueScope::class)]
class ScheduleRegenerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_id',
        'tournament_id',
        'user_id',
        'mode',
        'cutoff_round',
        'completed_rounds',
        'matches_created',
        'meta',
    ];

    protected $casts = [
        'cutoff_round' => 'integer',
        'completed_rounds' => 'integer',
        'matches_created' => 'integer',
        'meta' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
