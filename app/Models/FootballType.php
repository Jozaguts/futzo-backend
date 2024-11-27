<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

;

class FootballType extends Model
{
	use HasFactory, SoftDeletes;

	protected $fillable = [
		'name',
		'description',
		'status',
		'max_players_per_team',
		'min_players_per_team',
		'max_registered_players',
		'substitutions',
	];
	protected $hidden = ['created_at', 'updated_at'];

	public function leagues(): HasMany
	{
		return $this->hasMany(League::class);
	}
}
