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
        $tournament->league_id = auth()->user()->league_id;
        $tournament->saveQuietly();
        $defaultConfig = DefaultTournamentConfiguration::where([
            'tournament_format_id' => $tournament->tournament_format_id,
            'football_type_id' => $tournament->football_type_id,
        ])->first();
        $minMAx = json_decode(request()->input('basic.minMax'), true);
        $tournament
            ->configuration()
            ->save(TournamentConfiguration::create(
                array_merge(
                    [...$defaultConfig->toArray(), 'min_teams' => $minMAx[0], 'max_teams' => $minMAx[1]], [
                    'tournament_id' => $tournament->id
                ])));
        $tieBreakers = config('constants.tiebreakers');
        foreach ($tieBreakers as $tieBreaker) {
            $tieBreaker['tournament_configuration_id'] = $tournament->configuration->id;
            $tournament->configuration->tiebreakers()->create($tieBreaker);
        }
        $phases = config('constants.phases');
        if ($tournament->format->name === $this->TOURNAMENT_WITHOUT_PHASES) {
            $tournament->phases()->create($phases[0]);
        } else {
            $phasesWithoutGeneralTablePhase = array_filter($phases, fn($phase) => $phase['name'] !== $this->TOURNAMENT_WITHOUT_PHASES);
            foreach ($phasesWithoutGeneralTablePhase as $phase) {
                $tournament->phases()->create($phase);
            }
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
