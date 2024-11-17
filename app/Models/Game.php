<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
	use HasFactory, SoftDeletes;

	const STATUS_SCHEDULED = 'programado';
	const STATUS_IN_PROGRESS = 'en_progreso';
	const STATUS_COMPLETED = 'completado';
	const STATUS_POSTPONED = 'aplazado';
	const STATUS_CANCELED = 'cancelado';

	protected $fillable = [
		'date',
		'location',
		'home_team_id',
		'away_team_id',
		'category_id',
		'tournament_id',
		'winner_team_id',
		'league_id',
		'status'
	];
	protected $casts = [
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'date' => 'datetime',
	];

	public function winnerTeam()
	{
		return $this->belongsTo(Team::class, 'winner_team_id');
	}

	public function tournament(): BelongsTo
	{
		return $this->belongsTo(Tournament::class);
	}

	public function homeTeam(): BelongsTo
	{
		return $this->belongsTo(Team::class, 'home_team_id');
	}

	public function awayTeam(): BelongsTo
	{
		return $this->belongsTo(Team::class, 'away_team_id');
	}

	public function players(): BelongsToMany
	{
		return $this->belongsToMany(Player::class, 'game_player')
			->withPivot('entry_minute', 'exit_minute', 'goals', 'assists')
			->withTimestamps();
	}
}
