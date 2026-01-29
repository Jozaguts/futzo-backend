<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use Illuminate\Console\Command;

class SyncTournamentStatusCommand extends Command
{
    protected $signature = 'futzo:sync-tournament-status';

    protected $description = 'Actualiza torneos creados a "en curso" cuando su start_date ya llegó';

    public function handle(): int
    {
        $today = now()->toDateString();

        $updated = Tournament::query()
            ->where('status', 'creado')
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $today)
            ->update(['status' => 'en curso']);

        $this->info("Torneos actualizados a 'en curso': {$updated}");

        return self::SUCCESS;
    }
}
