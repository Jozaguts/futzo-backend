<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tournament extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'league_id',
        'name',
        'start_date',
        'end_date',
        'prize',
        'winner',
        'description',
        'status'
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    // Relación con equipos
    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    // Relación con jugadores a través de equipos
    public function players()
    {
        return $this->hasManyThrough(Player::class, Team::class);
    }

    // Relación con partidos
    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
