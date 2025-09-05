<?php

namespace App\Services;

use App\Models\LeagueField;
use App\Models\Tournament;

class ScheduleGeneratorV2
{
    public function __construct(private readonly AvailabilityService $availability)
    {
    }

    /**
     * Genera slots semanales por LeagueField para un torneo, usando las nuevas tablas.
     * Devuelve: [league_field_id => [[dow, start_min, end_min], ...]]
     */
    public function weeklySlotsForTournament(Tournament $tournament, int $stepMinutes = 15): array
    {
        $config = $tournament->configuration;
        $slotMinutes = (int)$config->game_time + (int)$config->time_between_games;

        // fields registrados para el torneo
        $fieldIds = $tournament->tournamentFields()->pluck('field_id')->all();
        if (empty($fieldIds)) return [];

        // league_fields (pivot) equivalentes para esta liga
        $leagueFields = LeagueField::query()
            ->where('league_id', $tournament->league_id)
            ->whereIn('field_id', $fieldIds)
            ->get(['id','field_id']);

        $out = [];
        foreach ($leagueFields as $lf) {
            $weekly = $this->availability->getWeeklyWindowsForLeagueField($lf->id);
            $slots = $this->availability->generateSlots($weekly, $slotMinutes, $stepMinutes);
            $out[$lf->id] = $slots;
        }

        return $out;
    }
}

