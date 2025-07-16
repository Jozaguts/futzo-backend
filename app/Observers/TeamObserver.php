<?php

namespace App\Observers;

use App\Models\DefaultLineup;
use App\Models\Team;

class TeamObserver
{
    public function created(Team $team): void
    {
        DefaultLineup::create([
            'team_id' => $team->id,
            'formation' => '4-4-2'
        ]);
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
