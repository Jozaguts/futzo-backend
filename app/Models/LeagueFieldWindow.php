<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueFieldWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_field_id',
        'day_of_week',
        'start_minute',
        'end_minute',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function leagueField(): BelongsTo
    {
        return $this->belongsTo(LeagueField::class);
    }
}

