<?php

namespace App\Http\Resources;

use App\Services\GroupConfigurationOptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $teamCount = $this->resource->teams->count();
        $formatName = $this->resource->format->name ?? null;
        $optionsService = new GroupConfigurationOptionService();
        $groupOptions = [];
        if ($formatName === 'Grupos y Eliminatoria') {
            $groupOptions = $optionsService->buildOptions($teamCount);
        }

        $selectedOptionId = null;
        $groupConfiguration = $this->resource->groupConfiguration;
        if ($groupConfiguration && !empty($groupOptions) && is_array($groupConfiguration->group_sizes)) {
            $storedSizes = array_map('intval', $groupConfiguration->group_sizes);
            rsort($storedSizes, SORT_NUMERIC);
            $advanceTopN = (int)$groupConfiguration->advance_top_n;
            $includeBestThirds = (bool)$groupConfiguration->include_best_thirds;
            $bestThirdsCount = $groupConfiguration->best_thirds_count;

            $matched = collect($groupOptions)->first(function (array $option) use (
                $storedSizes,
                $advanceTopN,
                $includeBestThirds,
                $bestThirdsCount
            ) {
                $payload = $option['group_phase'];

                return $payload['group_sizes'] === $storedSizes
                    && (int)$payload['advance_top_n'] === $advanceTopN
                    && (bool)$payload['include_best_thirds'] === $includeBestThirds
                    && (int)($payload['best_thirds_count'] ?? 0) === (int)($bestThirdsCount ?? 0);
            });

            if ($matched) {
                $selectedOptionId = $matched['id'];
            }
        }

        $configuration = $this->resource->configuration;
        $defaultRoundTrip = (bool)($configuration?->elimination_round_trip ?? false);

        return [
            'tournament_id' => $this->resource->id,
            'round_trip' => $this->resource->configuration->round_trip,
            'start_date' => $this->resource->start_date?->format('Y-m-d'),
            'end_date' => $this->resource->end_date?->format('Y-m-d'),
            'elimination_round_trip' => $this->resource->configuration->elimination_round_trip ?? null,
            'game_time' => $this->resource->configuration->game_time ?? null,
            'min_teams' => $this->resource->configuration->min_teams ?? null,
            'max_teams' => $this->resource->configuration->max_teams ?? null,
            'time_between_games' => $this->resource->configuration->time_between_games ?? null,
            'teams' => $this->resource->teams->count(),
            'teams_to_next_round' => $this->calculateTeamsToNextRound(),
            'format' => $this->resource->format ?? null,
            'footballType' => $this->resource->footballType ?? null,
            'locations' => $this->resource->locations ?? [],
            'tiebreakers' => $this->resource->configuration->tiebreakers ?? null,
            'phases' => $this->resource->tournamentPhases
                ->filter(fn($tp) => $tp->phase !== null)
                ->map(function ($tournamentPhase) use ($defaultRoundTrip) {
                $rulesModel = $tournamentPhase->rules;

                $roundTrip = $rulesModel?->round_trip;
                if ($roundTrip === null) {
                    $roundTrip = $defaultRoundTrip;
                }

                $awayGoals = $rulesModel?->away_goals;
                if ($awayGoals === null) {
                    $awayGoals = false;
                }

                $extraTime = $rulesModel?->extra_time;
                if ($extraTime === null) {
                    $extraTime = true;
                }

                $penalties = $rulesModel?->penalties;
                if ($penalties === null) {
                    $penalties = true;
                }

                $advanceIfTie = $rulesModel?->advance_if_tie ?? 'better_seed';

                return [
                    'id' => $tournamentPhase->phase->id,
                    'name' => $tournamentPhase->phase->name,
                    'min_teams_for' => $tournamentPhase->phase->min_teams_for,
                    'is_active' => $tournamentPhase->is_active,
                    'is_completed' => $tournamentPhase->is_completed,
                    'tournament_id' => $this->resource->id,
                    'rules' => [
                        'round_trip' => (bool)$roundTrip,
                        'away_goals' => (bool)$awayGoals,
                        'extra_time' => (bool)$extraTime,
                        'penalties' => (bool)$penalties,
                        'advance_if_tie' => $advanceIfTie,
                    ],
                ];
            })->all(),
            'group_phase' => $this->resource->groupConfiguration ? [
                'teams_per_group' => $this->resource->groupConfiguration->teams_per_group,
                'advance_top_n' => $this->resource->groupConfiguration->advance_top_n,
                'include_best_thirds' => (bool)$this->resource->groupConfiguration->include_best_thirds,
                'best_thirds_count' => $this->resource->groupConfiguration->best_thirds_count,
                'group_sizes' => $this->resource->groupConfiguration->group_sizes
                    ? array_map('intval', $this->resource->groupConfiguration->group_sizes)
                    : null,
            ] : null,
            'group_phase_option_id' => $selectedOptionId,
            'group_configuration_options' => $groupOptions,
        ];
    }

    private function calculateTeamsToNextRound(): int
    {
        if ($this->resource->format->name === 'Torneo de Liga') {
            return 1;
        }

        $tournamentPhases = $this->resource->tournamentPhases;

        if ($tournamentPhases instanceof \Illuminate\Database\Eloquent\Collection) {
            $tournamentPhases->loadMissing('phase');
        }

        $activeTournamentPhase = $tournamentPhases?->firstWhere('is_active', true);

        $eliminationMapping = [
            'Dieciseisavos de Final' => 32,
            'Octavos de Final' => 16,
            'Cuartos de Final' => 8,
            'Semifinales' => 4,
            'Final' => 2,
        ];

        if ($activeTournamentPhase && $activeTournamentPhase->phase) {
            $phaseName = $activeTournamentPhase->phase->name;

            if (array_key_exists($phaseName, $eliminationMapping)) {
                return $eliminationMapping[$phaseName];
            }
        }

        $groupConfiguration = $this->resource->groupConfiguration;

        if (!$groupConfiguration) {
            return 0;
        }

        $groupSizes = $groupConfiguration->group_sizes;

        if (is_array($groupSizes) && !empty($groupSizes)) {
            $normalizedGroupSizes = array_values(array_filter(
                array_map(static fn($size) => (int)$size, $groupSizes),
                static fn(int $size) => $size > 0
            ));
            $groupCount = count($normalizedGroupSizes);
        } else {
            $teamsPerGroup = (int)($groupConfiguration->teams_per_group ?? 0);
            $totalTeams = $this->resource->teams->count();
            $groupCount = $teamsPerGroup > 0
                ? (int)ceil($totalTeams / $teamsPerGroup)
                : 0;
        }

        if ($groupCount <= 0) {
            return 0;
        }

        $advanceTopN = max(0, (int)($groupConfiguration->advance_top_n ?? 0));
        $qualifiedTeams = $groupCount * $advanceTopN;

        if ($qualifiedTeams <= 0) {
            return 0;
        }

        if ($groupConfiguration->include_best_thirds) {
            $qualifiedTeams += (int)($groupConfiguration->best_thirds_count ?? 0);
        }

        if ($qualifiedTeams <= 0) {
            return 0;
        }

        foreach ([2, 4, 8, 16, 32] as $bracketSize) {
            if ($qualifiedTeams <= $bracketSize) {
                return $bracketSize;
            }
        }

        return 32;
    }
}
