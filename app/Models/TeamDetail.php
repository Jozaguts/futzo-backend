<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'colors'
    ];

    protected $casts = [
        'colors' => 'json'
    ];
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
