<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TournamentPhase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'tournament_id', 'is_active', 'is_completed'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
}
