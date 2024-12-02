<?php

namespace App\Observers;

use App\Models\DefaultTournamentConfiguration;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;

class TournamentObserver
{
	/**
	 * Handle the Tournament "created" event.
	 */
	public function created(Tournament $tournament): void
	{
		$tournament->league_id = auth()->user()->league_id;
		$tournament->saveQuietly();
		$defaultConfig = DefaultTournamentConfiguration::where([
			'tournament_format_id' => $tournament->tournament_format_id,
			'football_type_id' => $tournament->football_type_id,
		])->first();
		$tournament
			->configuration()
			->save(TournamentConfiguration::create(array_merge($defaultConfig->toArray(), [
				'tournament_id' => $tournament->id])));


	}


	/**
	 * Handle the Tournament "updated" event.
	 */
	public function updated(Tournament $tournament): void
	{
		//
	}

	/**
	 * Handle the Tournament "deleted" event.
	 */
	public function deleted(Tournament $tournament): void
	{
		//
	}

	/**
	 * Handle the Tournament "restored" event.
	 */
	public function restored(Tournament $tournament): void
	{
		//
	}

	/**
	 * Handle the Tournament "force deleted" event.
	 */
	public function forceDeleted(Tournament $tournament): void
	{
		//
	}
}
