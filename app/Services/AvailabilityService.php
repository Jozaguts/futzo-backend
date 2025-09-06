<?php

namespace App\Services;

use App\Models\FieldWindow;
use App\Models\LeagueField;
use App\Models\LeagueFieldWindow;
use App\Models\TournamentFieldReservation;

class AvailabilityService
{
    /**
     * Calcula ventanas efectivas por día (0..6) para una liga-campo:
     * intersección de FieldWindows ∩ LeagueFieldWindows − TournamentReservations (exclusivas).
     */
    public function getWeeklyWindowsForLeagueField(int $leagueFieldId, ?int $excludeTournamentId = null): array
    {
        $lf = LeagueField::findOrFail($leagueFieldId);

        $result = [];
        for ($dow = 0; $dow <= 6; $dow++) {
            $base = FieldWindow::query()
                ->where('field_id', $lf->field_id)
                ->where('day_of_week', $dow)
                ->where('enabled', true)
                ->get(['start_minute', 'end_minute'])
                ->map(fn($w) => [$w->start_minute, $w->end_minute])
                ->all();

            $league = LeagueFieldWindow::query()
                ->where('league_field_id', $leagueFieldId)
                ->where('day_of_week', $dow)
                ->where('enabled', true)
                ->get(['start_minute', 'end_minute'])
                ->map(fn($w) => [$w->start_minute, $w->end_minute])
                ->all();

            $reservationsQ = TournamentFieldReservation::query()
                ->where('league_field_id', $leagueFieldId)
                ->where('day_of_week', $dow)
                ->where('exclusive', true);

            if ($excludeTournamentId) {
                $reservationsQ->where('tournament_id', '!=', $excludeTournamentId);
            }
            $reservations = $reservationsQ->get(['start_minute', 'end_minute'])
                ->map(fn($w) => [$w->start_minute, $w->end_minute])
                ->all();

            $eff = $this->intersectMany($base, $league);
            $eff = $this->subtractMany($eff, $reservations);
            $result[$dow] = $this->normalize($eff);
        }

        return $result; // [dow => [[start,end], ...]]
    }

    /**
     * Genera slots alineados para cada día dado un tamaño de bloque.
     */
    public function generateSlots(array $weeklyWindows, int $slotMinutes, int $step = 15): array
    {
        $slots = [];
        foreach ($weeklyWindows as $dow => $windows) {
            foreach ($windows as [$s, $e]) {
                $cursor = $this->ceilToStep($s, $step);
                while ($cursor + $slotMinutes <= $e) {
                    $slots[] = [$dow, $cursor, $cursor + $slotMinutes];
                    $cursor += $slotMinutes;
                }
            }
        }
        return $slots;
    }

    private function ceilToStep(int $min, int $step): int
    {
        $r = $min % $step;
        return $r === 0 ? $min : ($min + ($step - $r));
    }

    /**
     * Normaliza: ordena y fusiona solapamientos de una lista de rangos [s,e).
     */
    private function normalize(array $ranges): array
    {
        if (empty($ranges)) return [];
        usort($ranges, fn($a,$b) => $a[0] <=> $b[0]);
        $out = [];
        [$cs, $ce] = $ranges[0];
        foreach ($ranges as [$s,$e]) {
            if ($s <= $ce) {
                $ce = max($ce, $e);
            } else {
                $out[] = [$cs, $ce];
                [$cs, $ce] = [$s, $e];
            }
        }
        $out[] = [$cs, $ce];
        return $out;
    }

    /**
     * Intersección de dos conjuntos de rangos (asumidos normalizados y sin solapes dentro de cada set).
     */
    private function intersectMany(array $a, array $b): array
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        $i = $j = 0; $out = [];
        while ($i < count($a) && $j < count($b)) {
            [$as,$ae] = $a[$i];
            [$bs,$be] = $b[$j];
            $s = max($as,$bs); $e = min($ae,$be);
            if ($s < $e) $out[] = [$s,$e];
            if ($ae < $be) $i++; else $j++;
        }
        return $out;
    }

    /**
     * Resta (A − B) de conjuntos de rangos.
     */
    private function subtractMany(array $a, array $b): array
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        $out = [];
        foreach ($a as [$as,$ae]) {
            $segments = [[$as,$ae]];
            foreach ($b as [$bs,$be]) {
                $next = [];
                foreach ($segments as [$s,$e]) {
                    if ($be <= $s || $bs >= $e) {
                        $next[] = [$s,$e];
                    } else {
                        if ($s < $bs) $next[] = [$s, $bs];
                        if ($be < $e) $next[] = [$be, $e];
                    }
                }
                $segments = $next;
                if (empty($segments)) break;
            }
            array_push($out, ...$segments);
        }
        return $this->normalize($out);
    }
}
