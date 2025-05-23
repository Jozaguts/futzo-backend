<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'home' => [
                'id' => $this->homeTeam->id,
                'name' => $this->homeTeam->name,
                'image' => 'https://ui-avatars.com/api/?name=' . $this->homeTeam->name . '&background=9155FD&color=fff',
                'goals' => $this->home_goals
            ],
            'away' => [
                'id' => $this->awayTeam->id,
                'name' => $this->awayTeam->name,
                'image' => 'https://ui-avatars.com/api/?name=' . $this->awayTeam->name . '&background=8A8D93&color=fff',
                'goals' => $this->away_goals
            ],
            'details' => [
                'date' => optional($this->match_date)->translatedFormat('D j/n'),
                'raw_date' => optional($this->match_date)->toDateTimeString(),
                'raw_time' => $this->match_time,
                'time' => $this->match_time
                    ? [
                        'hours' => Carbon::createFromFormat('H:i', $this->match_time)->hour,
                        'minutes' => Carbon::createFromFormat('H:i', $this->match_time)->minute,
                    ]
                    : null,
                'field' => [
                    'id' => $this->field_id,
                    'name' => optional($this->field)->name ?? 'Campo desconocido'

                ],
                'location' => [
                    'id' => $this->location_id,
                    'name' => optional($this->location)->name ?? 'Ubicación desconocida'
                ],
                'referee' => optional($this->referee)->name ?? 'Por asignar'
            ],
            'status' => $this->status,
            'result' => $this->result,
            'start_date' => $this->tournament->start_date,
        ];
    }
}
