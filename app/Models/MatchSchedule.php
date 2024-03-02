<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatchSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tournament_id',
        'home_team_id',
        'away_team_id',
        'match_date',
        'match_time',
        'location_id',
        'referee_id',
        'status',
        'result'
    ];
    protected  $casts = [
        'match_date' => 'date',
        'match_time' => 'time'
    ];
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }
    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }
    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    public function referee()
    {
        return $this->belongsTo(Referee::class);
    }
}
