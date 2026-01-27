<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Services\TournamentScheduleRegenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class RegenerateScheduleWithFixedRoundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tournamentId,
        public int $roundId,
        public array $matches,
        public ?int $byeTeamId,
        public ?int $userId,
    ) {
    }

    public function handle(TournamentScheduleRegenerationService $service): void
    {
        if ($this->userId) {
            Auth::loginUsingId($this->userId);
        }

        $tournament = Tournament::findOrFail($this->tournamentId);
        $service->regenerateWithFixedRound($tournament, $this->roundId, $this->matches, $this->byeTeamId);

        if ($this->userId) {
            Auth::logout();
        }
    }
}
