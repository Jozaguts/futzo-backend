<?php

namespace App\Listeners;

use App\Events\TournamentCreatedEvent;
use App\Models\DefaultTournamentConfiguration;
use App\Models\Phase;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;

class TournamentCreatedListener
{
    private string $TOURNAMENT_WITHOUT_PHASES = 'Torneo de Liga';

    public function __construct()
    {
    }

    /**
     * @throws \JsonException
     */
    public function handle(TournamentCreatedEvent $event): void
    {
        $minMAx = $event->basicFields['minMax'];
        $defaultConfig = DefaultTournamentConfiguration::where([
            'tournament_format_id' => $event->tournament->tournament_format_id,
            'football_type_id' => $event->tournament->football_type_id,
        ])->first();
        if (!empty($minMAx)) {
            $minMAx = json_decode($minMAx, true, 512, JSON_THROW_ON_ERROR);
            $event->tournament
                ->configuration()
                ->save(TournamentConfiguration::create(
                    array_merge(
                        [...$defaultConfig->toArray(), 'min_teams' => $minMAx[0], 'max_teams' => $minMAx[1]], [
                        'tournament_id' => $event->tournament->id
                    ])));
        }

        $tieBreakers = config('constants.tiebreakers');
        foreach ($tieBreakers as $tieBreaker) {
            $tieBreaker['tournament_configuration_id'] = $event->tournament->configuration->id;
            $event->tournament->configuration->tiebreakers()->create($tieBreaker);
        }
        if ($event->tournament->format->name === $this->TOURNAMENT_WITHOUT_PHASES) {
            $phase = Phase::first('name', $this->TOURNAMENT_WITHOUT_PHASES)->first();
            $event->tournament->phases()->create([
                'phase_id' => $phase->id,
                'tournament_id' => $event->tournament->id,
                'is_active' => true,
                'is_completed' => false,
            ]);
        } else {
            $phasesWithoutGeneralTablePhase = Phase::whereNot('name', $this->TOURNAMENT_WITHOUT_PHASES)->get();
            foreach ($phasesWithoutGeneralTablePhase as $phase) {
                $event->tournament->phases()->create([
                    'phase_id' => $phase->id,
                    'tournament_id' => $event->tournament->id,
                    'is_active' => true,
                    'is_completed' => false,
                ]);
            }
        }

    }
}
