<?php

namespace App\Observers;

use App\Models\Tournament;

class TournamentObserver
{
    /**
     * Handle the Tournament "created" event.
     */
    public function creating(Tournament $tournament): void
    {
        $tournament->league_id = auth()->user()->league_id;
    }
    public function created(Tournament $tournament): void
    {
        $tournament->league_id = auth()->user()->league_id;
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
