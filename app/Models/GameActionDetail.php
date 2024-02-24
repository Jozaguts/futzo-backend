<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameActionDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'action_id',
        'game_id',
        'player_id',
        'minute',
        'comments',
    ];
}
