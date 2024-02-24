<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lineup extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['game_id', 'player_id', 'first_team_player','round'];
}
