<?php

namespace App\Listeners;

use App\Events\TournamentCreatedEvent;
use App\Models\DefaultTournamentConfiguration;
use App\Models\Phase;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use TournamentFormatId;

class TournamentCreatedListener
{
    private const FALLBACK_PHASE = 'Tabla general';
    private const FORMAT_PHASES = [
        TournamentFormatId::League->value => ['Tabla general'],
        TournamentFormatId::LeagueAndElimination->value => ['Tabla general', 'Dieciseisavos de Final', 'Octavos de Final', 'Cuartos de Final', 'Semifinales', 'Final'],
        TournamentFormatId::GroupAndElimination->value => ['Fase de grupos', 'Dieciseisavos de Final', 'Octavos de Final', 'Cuartos de Final', 'Semifinales', 'Final'],
        TournamentFormatId::Elimination->value => ['Dieciseisavos de Final', 'Octavos de Final', 'Cuartos de Final', 'Semifinales', 'Final'],
        TournamentFormatId::Swiss->value => ['Tabla general'],
    ];

    public function __construct()
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
                            ...$defaultConfig->toArray(),
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
        $this->assignPhases($event->tournament);
    }

    private function assignPhases(Tournament $tournament): void
    {
        $phaseNames = collect(self::FORMAT_PHASES[$tournament->tournament_format_id] ?? [self::FALLBACK_PHASE]);
        $phases = Phase::whereIn('name', $phaseNames)->get()->keyBy('name');

        $phaseNames->each(function (string $phaseName, int $index) use ($phases, $tournament) {
            /** @var Phase|null $phase */
            $phase = $phases->get($phaseName);
            if (!$phase) {
                return;
            }

            $tournament->tournamentPhases()->firstOrCreate(
                ['phase_id' => $phase->id],
                [
                    'is_active' => $index === 0,
                    'is_completed' => false,
                ]
            );
        });
    }
}
