<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeagueLocation extends Model
{
    use SoftDeletes;

    protected $table = 'league_location';

    protected $fillable = [
        'location_id',
        'league_id',
        'availability',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    protected function casts(): array
    {
        return [
            'availability' => 'array',
        ];
    }
}
