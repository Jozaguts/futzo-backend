<?php

namespace App\Listeners;

use App\Events\TournamentCreatedEvent;
use App\Models\DefaultTournamentConfiguration;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Services\TournamentPhaseService;

class TournamentCreatedListener
{
    public function __construct(private readonly TournamentPhaseService $phaseService)
    {
    }

    /**
     * @throws \JsonException
     */
    public function handle(TournamentCreatedEvent $event): void
    {
        $minMAx = $event->basicFields['min_max'];
        $substitutions_per_team = $event->basicFields['substitutions_per_team'];
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
                        [
                            ...$defaultConfig?->toArray(),
                            'min_teams' => $minMAx[0],
                            'max_teams' => $minMAx[1],
                            'substitutions_per_team' => $substitutions_per_team,
                        ], [
                        'tournament_id' => $event->tournament->id
                    ])));
        }

        $tieBreakers = config('constants.tiebreakers');
        foreach ($tieBreakers as $tieBreaker) {
            $tieBreaker['tournament_configuration_id'] = $event->tournament->configuration->id;
            $event->tournament->configuration->tiebreakers()->create($tieBreaker);
        }
        $this->phaseService->sync($event->tournament->fresh());
    }
}
