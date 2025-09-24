<?php

namespace App\Observers;

use App\Models\DefaultLineup;
use App\Models\Formation;
use App\Models\Team;

class TeamObserver
{
    public function created(Team $team): void
    {
        if (!$team->defaultLineup()->exists()) {
            $team->defaultLineup()->create([
                'formation_id' => Formation::first()->id
            ]);
        }
    }

    public function updated(Team $team): void
    {
    }

    public function deleted(Team $team): void
    {
    }

    public function restored(Team $team): void
    {
    }
}
