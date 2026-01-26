<?php

namespace App\Observers;

use App\Jobs\SyncQrConfigurationsJob;
use App\Models\League;

class LeagueObserver
{
    public function created(League $league): void
    {
        SyncQrConfigurationsJob::dispatch()->afterCommit();
    }
}
