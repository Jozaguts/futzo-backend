<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamTournament extends Pivot
{
    protected $table = 'team_tournament';
    protected $guarded = [];

    protected $casts = [
        'home_day_of_week' => 'integer',
        'home_start_time' => 'datetime:H:i:s',
    ];

    public function homeLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'home_location_id');
    }

    public function homeField(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'home_field_id');
    }

    protected function homeStartTime(): Attribute
    {
        return Attribute ::make(
            get: fn($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : null,
        );
    }
}
