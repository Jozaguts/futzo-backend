<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name','city','address','availability'];

    protected $casts = [
        'availability' => 'json'
    ];

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'location_tournament')
            ->using(LocationTournament::class);
    }
}
