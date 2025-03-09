<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'round_trip' => $this->configuration->round_trip,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'elimination_round_trip' => $this->configuration->elimination_round_trip ?? null,
            'game_time' => $this->configuration->game_time ?? null,
            'min_teams' => $this->configuration->min_teams ?? null,
            'max_teams' => $this->configuration->max_teams ?? null,
            'time_between_games' => $this->configuration->time_between_games ?? null,
            'teams' => $this->teams->count(),
            'teams_to_next_round' => $this->calculateTeamsToNextRound(),
            'format' => $this->format ?? null,
            'footballType' => $this->footballType ?? null,
            'locations' => $this->locations ?? [],
            'tiebreakers' => $this->configuration->tiebreakers ?? null,
            'phases' => $this->phases ?? null,
        ];
    }

    private function calculateTeamsToNextRound(): int
    {
        $teamsToNextRound = 0;
        if ($this->format->name === 'Torneo de Liga') {
            return 1;
        }
        $activePhase = collect($this->phases->where('is_active', true)->all());
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
