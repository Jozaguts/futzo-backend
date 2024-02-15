<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['date','location', 'home_team_id', 'away_team_id','category_id','tournament_id','winner_team_id'];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function winnerTeam()
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }
}
