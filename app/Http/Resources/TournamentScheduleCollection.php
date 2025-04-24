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
                        return [
                            'id' => $match->id,

                            'home' => [
                                'id' => $match->homeTeam->id,
                                'name' => $match->homeTeam->name,
                                'image' => 'https://ui-avatars.com/api/?name=' . $match->homeTeam->name . '&background=9155FD&color=fff',
                                'goals' => $match->home_goals
                            ],
                            'away' => [
                                'id' => $match->awayTeam->id,
                                'name' => $match->awayTeam->name,
                                'image' => 'https://ui-avatars.com/api/?name=' . $match->awayTeam->name . '&background=8A8D93&color=fff',
                                'goals' => $match->away_goals
                            ],
                            'details' => [
                                'date' => optional($match->match_date)->translatedFormat('D j/n'),
                                'time' => optional($match->match_time)->format('h:i A'),
                                'field' => [
                                    'id' => $match->field_id,
                                    'name' => optional($match->field)->name ?? 'Campo desconocido'
                                ],
                                'location' => [
                                    'id' => $match->location_id,
                                    'name' => optional($match->location)->name ?? 'Ubicación desconocida'
                                ],
                                'referee' => optional($match->referee)->name ?? 'Por asignar'
                            ],
                            'status' => $match->status,
                            'result' => $match->result,
                        ];
                    })->values(),

                ];
            })->values()->toArray();
    }

}
