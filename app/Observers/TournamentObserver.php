<?php

namespace App\Observers;

use App\Models\Tournament;
use App\Models\User;

class TournamentObserver
{
    private string $TOURNAMENT_WITHOUT_PHASES = 'Torneo de Liga';

    /**
     * Handle the Tournament "created" event.
     * @throws \JsonException
     */
    public function created(Tournament $tournament): void
    {
        if (auth()->check() && is_null($tournament->league_id)) {
            $tournament->league_id = auth()->user()->league_id;
            $tournament->saveQuietly();
        }

        if ($owner = $this->resolveOwner($tournament)) {
            $owner->incrementTournamentUsage();
        }
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
        if ($owner = $this->resolveOwner($tournament)) {
            $owner->decrementTournamentUsage();
        }
    }

    /**
     * Handle the Tournament "restored" event.
     */
    public function restored(Tournament $tournament): void
    {
        if ($owner = $this->resolveOwner($tournament)) {
            $owner->incrementTournamentUsage();
        }
    }

    /**
     * Handle the Tournament "force deleted" event.
     */
    public function forceDeleted(Tournament $tournament): void
    {
        if ($owner = $this->resolveOwner($tournament)) {
            $owner->decrementTournamentUsage();
        }
    }

    private function resolveOwner(Tournament $tournament): ?User
    {
        $tournament->loadMissing('league.owner');
        if ($tournament->league && $tournament->league->owner) {
            return $tournament->league->owner;
        }

        return auth()->user();
    }
}
