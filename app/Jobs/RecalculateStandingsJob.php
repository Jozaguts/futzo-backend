<?php

namespace App\Jobs;

use App\Services\StandingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RecalculateStandingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tournamentId;
    public ?int $tournamentPhaseId;
    public ?int $triggeringGameId;

    public function __construct(int $tournamentId, ?int $tournamentPhaseId, ?int $triggeringGameId)
    {
        $this->tournamentId = $tournamentId;
        $this->tournamentPhaseId = $tournamentPhaseId;
        $this->triggeringGameId = $triggeringGameId;
    }

    /**
     * @throws Throwable
     */
    public function handle(StandingsService $service): void
    {
        $service->recalculateStandingsForPhase($this->tournamentId, $this->tournamentPhaseId, $this->triggeringGameId);
    }
}
