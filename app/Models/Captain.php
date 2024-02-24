<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Captain extends Model
{
    use HasFactory, softDeletes;
    protected $fillable = ['game_id','team_id', 'player_id'];
}
