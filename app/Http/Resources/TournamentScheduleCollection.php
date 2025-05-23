<?php

namespace App\Http\Resources;

use App\Services\RoundStatusService;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TournamentScheduleCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return $this->collection
            ->groupBy('round')
            ->map(function ($matches, $round) {
                $roundStatus = RoundStatusService::getRoundStatus(
                    $matches->first()->tournament_id,
                    $round
                );
                return [
                    'round' => (int)$round,
                    'status' => $roundStatus,
                    'isEditable' => false,
                    'date' => optional($matches->first())->match_date?->toDateString(),
                    'matches' => $matches->map(function ($match) {
                        return GameResource::make($match);
                    })->values(),

                ];
            })->values()->toArray();
    }

}
