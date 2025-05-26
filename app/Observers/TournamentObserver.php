<?php

namespace App\Observers;

use App\Models\DefaultTournamentConfiguration;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;

class TournamentObserver
{
    private string $TOURNAMENT_WITHOUT_PHASES = 'Torneo de Liga';

    /**
     * Handle the Tournament "created" event.
     * @throws \JsonException
     */
    public function created(Tournament $tournament): void
    {
        if (auth()->check()) {
            $tournament->league_id = auth()->user()->league_id;
            $tournament->saveQuietly();
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
