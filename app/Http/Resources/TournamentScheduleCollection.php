<?php

namespace App\Http\Resources;

use App\Services\RoundStatusService;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

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

                $byeTeam = $this->resolveByeTeam($matches);

                return [
                    'round' => (int)$round,
                    'status' => $roundStatus,
                    'isEditable' => false,
                    'date' => optional($matches->first())->match_date?->toDateString(),
                    'matches' => $matches->map(function ($match) {
                        return GameResource::make($match);
                    })->values(),
                    'bye_team' => $byeTeam,

                ];
            })->values()->toArray();
    }

    private function resolveByeTeam(Collection $matches): ?array
    {
        $firstMatch = $matches->first();

        if (!$firstMatch || !$firstMatch->relationLoaded('tournament')) {
            return null;
        }

        $tournament = $firstMatch->tournament;

        if (!$tournament || !$tournament->relationLoaded('teams')) {
            return null;
        }

        $teams = $tournament->teams;

        if (!$teams instanceof Collection) {
            $teams = collect($teams);
        }

        if ($teams->isEmpty() || $teams->count() % 2 === 0) {
            return null;
        }

        $playingTeamIds = $matches
            ->flatMap(static function ($match) {
                return [
                    $match->home_team_id,
                    $match->away_team_id,
                ];
            })
            ->filter()
            ->unique();

        $byeTeam = $teams->first(function ($team) use ($playingTeamIds) {
            return !$playingTeamIds->contains($team->id);
        });

        if (!$byeTeam) {
            return null;
        }

        return [
            'id' => $byeTeam->id,
            'name' => $byeTeam->name,
            'image' => $byeTeam->image,
        ];
    }

}
