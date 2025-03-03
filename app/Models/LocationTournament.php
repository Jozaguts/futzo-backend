<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationTournament extends Pivot
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['location_id', 'tournament_id'];
}
