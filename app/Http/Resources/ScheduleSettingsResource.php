<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'game_time' => $this->configuration->game_time ?? null,
            'min_teams' => $this->configuration->min_teams ?? null,
            'max_teams' => $this->configuration->max_teams ?? null,
            'time_between_games' => $this->configuration->time_between_games ?? null,
            'teams' => $this->teams->count(),
            'format' => $this->format ?? null,
            'footballType' => $this->footballType ?? null,
            'locations' => $this->locations ?? null,
            'tiebreakers' => $this->configuration->tiebreakers ?? null,
            'phases' => $this->phases ?? null,
        ];
    }
}
