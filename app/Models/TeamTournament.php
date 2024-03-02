<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamTournament extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable =['team_id', 'tournament_id', 'category_id'];
}
