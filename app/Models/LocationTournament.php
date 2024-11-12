<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class LocationTournament extends Pivot
{
    use HasFactory;

    protected $fillable = ['location_id', 'tournament_id'];
}
