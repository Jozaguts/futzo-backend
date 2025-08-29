<?php

namespace App\Services;

use App\Models\League;
use App\Models\User;

class LeagueStatusSyncService
{
    public function syncForOwner(User $user): void
    {
        $isOperational = $user->isOperationalForBilling();

        League::where('owner_id', $user->id)->get()->each(function (League $league) use ($isOperational) {
            if ($league->status === League::STATUS_ARCHIVED) {
                return; // no tocar archivadas
            }
            $target = $isOperational ? League::STATUS_READY : League::STATUS_SUSPENDED;
            if ($league->status !== $target) {
                $league->status = $target;
                $league->save();
            }
        });
    }
}
