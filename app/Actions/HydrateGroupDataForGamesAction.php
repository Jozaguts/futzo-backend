<?php

namespace App\Actions;

use App\Enums\TournamentFormatId;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HydrateGroupDataForGamesAction
{
    public function execute(Tournament $tournament, Collection $games): Collection
    {
        $includeGroupData = (int)($tournament->configuration?->tournament_format_id ?? $tournament->tournament_format_id)
            === TournamentFormatId::GroupAndElimination->value;

        if (!$includeGroupData) {
            return $games;
        }

        $assignments = DB::table('team_tournament')
            ->select('team_id', 'group_key')
            ->where('tournament_id', $tournament->id)
            ->whereNotNull('group_key')
            ->get();

        if ($assignments->isEmpty()) {
            return $games;
        }

        $teamIds = $assignments->pluck('team_id')->unique();
        $teamsById = Team::whereIn('id', $teamIds)
            ->get(['id', 'name', 'image', 'colors'])
            ->keyBy('id');

        $teamGroupMap = $assignments->pluck('group_key', 'team_id')->toArray();
        $groupSummaries = $this->buildGroupSummaries($teamGroupMap, $teamsById);

        return $games->map(function (Game $game) use ($teamGroupMap, $groupSummaries, $teamsById) {
            $homeGroup = $teamGroupMap[$game->home_team_id] ?? null;
            $awayGroup = $teamGroupMap[$game->away_team_id] ?? null;
            $gameGroupKey = $game->group_key ?? null;

            if (!is_null($homeGroup)) {
                $game->setAttribute('home_group_key', $homeGroup);
            }

            if (!is_null($awayGroup)) {
                $game->setAttribute('away_group_key', $awayGroup);
            }

            if (!$gameGroupKey && $homeGroup && $homeGroup === $awayGroup) {
                $gameGroupKey = $homeGroup;
            }

            if ($gameGroupKey) {
                $summary = $groupSummaries->get($gameGroupKey)
                    ?? $this->buildGroupSummary($gameGroupKey, $teamGroupMap, $teamsById);
                $game->setAttribute('group_key', $gameGroupKey);
                $game->setAttribute('group_summary', $summary);
            }

            return $game;
        });
    }

    private function buildGroupSummaries(array $teamGroupMap, Collection $teamsById): Collection
    {
        return collect($teamGroupMap)
            ->mapToGroups(static function ($groupKey, $teamId) {
                return [$groupKey => $teamId];
            })
            ->map(function ($teamIds, $groupKey) use ($teamsById) {
                $groupTeams = $teamIds->map(function ($teamId) use ($teamsById) {
                    $team = $teamsById->get($teamId);

                    if (!$team) {
                        return null;
                    }

                    return [
                        'id' => $team->id,
                        'name' => $team->name,
                        'image' => $team->image,
                    ];
                })->filter()->values()->all();

                return [
                    'key' => $groupKey,
                    'name' => "Grupo {$groupKey}",
                    'teams_count' => count($groupTeams),
                    'teams' => $groupTeams,
                ];
            });
    }

    private function buildGroupSummary(string $groupKey, array $teamGroupMap, Collection $teamsById): array
    {
        $teamIds = array_keys($teamGroupMap, $groupKey, true);

        $groupTeams = collect($teamIds)
            ->map(function ($teamId) use ($teamsById) {
                $team = $teamsById->get((int) $teamId);

                if (!$team) {
                    return null;
                }

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'image' => $team->image,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'key' => $groupKey,
            'name' => "Grupo {$groupKey}",
            'teams_count' => count($groupTeams),
            'teams' => $groupTeams,
        ];
    }
}
