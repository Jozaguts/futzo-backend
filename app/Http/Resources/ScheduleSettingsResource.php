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
        $optionsService = new GroupConfigurationOptionService();
        $groupOptions = $optionsService->buildOptions($teamCount);

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

        return [
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
            'phases' => $this->resource->tournamentPhases->load(['phase','rules'])->map(function ($tournamentPhase) {
                return [
                    'id' => $tournamentPhase->phase->id,
                    'name' => $tournamentPhase->phase->name,
                    'is_active' => $tournamentPhase->is_active,
                    'is_completed' => $tournamentPhase->is_completed,
                    'tournament_id' => $this->resource->id,
                    'rules' => $tournamentPhase->rules ? [
                        'round_trip' => (bool)$tournamentPhase->rules->round_trip,
                        'away_goals' => (bool)$tournamentPhase->rules->away_goals,
                        'extra_time' => (bool)$tournamentPhase->rules->extra_time,
                        'penalties' => (bool)$tournamentPhase->rules->penalties,
                        'advance_if_tie' => $tournamentPhase->rules->advance_if_tie,
                    ] : null,
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
        $teamsToNextRound = 0;
        if ($this->resource->format->name === 'Torneo de Liga') {
            return 1;
        }
        $activePhase = collect($this->resource->phases->where('is_active', true)->all());
        $roundOf16activated = $activePhase->where('name', 'Octavos de Final')->isNotEmpty();
        if ($roundOf16activated) {
            return 16;
        }
        $quarterFinalsActivated = $activePhase->where('name', 'Cuartos de Final')->isNotEmpty();
        if ($quarterFinalsActivated) {
            return 8;
        }
        $semiFinalsActivated = $activePhase->where('name', 'Semifinales')->isNotEmpty();
        if ($semiFinalsActivated) {
            return 4;
        }
        $finalActivated = $activePhase->where('name', 'Final')->isNotEmpty();
        if ($finalActivated) {
            return 2;
        }
        return $teamsToNextRound;
    }
}
