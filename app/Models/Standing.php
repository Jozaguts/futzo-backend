<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Standing extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','team_tournament_id','pg','w','d','l','gf','ga','gd', 'pts', 'last_5',
    ];
}
