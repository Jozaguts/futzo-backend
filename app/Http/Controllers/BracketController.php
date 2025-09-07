<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\BracketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BracketController extends Controller
{
    public function groupStandings(Request $request, Tournament $tournament): JsonResponse
    {
        $phaseId = $request->query('phase_id');
        $data = app(BracketService::class)->groupStandings($tournament->id, $phaseId);
        return response()->json(['tournament_id' => $tournament->id, 'groups' => $data]);
    }

    public function preview(Request $request, Tournament $tournament): JsonResponse
    {
        $phase = $request->query('phase', 'Cuartos de Final');
        $data = app(BracketService::class)->previewSeeding($tournament, $phase);
        return response()->json($data);
    }

    public function confirm(Request $request, Tournament $tournament): JsonResponse
    {
        $data = $request->validate([
            'phase' => 'required|string',
            'round_trip' => 'sometimes|boolean',
            'min_rest_minutes' => 'sometimes|integer|min:0',
            'matches' => 'required|array|min:1',
            'matches.*.home_team_id' => 'required|integer|exists:teams,id',
            'matches.*.away_team_id' => 'required|integer|exists:teams,id',
            'matches.*.field_id' => 'required|integer|exists:fields,id',
            'matches.*.match_date' => 'required|date',
            'matches.*.match_time' => 'required|date_format:H:i',
            'matches.*.leg' => 'sometimes|integer|min:1',
        ]);

        // Fase destino
        $tp = $tournament->tournamentPhases()
            ->whereHas('phase', fn($q) => $q->where('name', $data['phase']))
            ->first();
        abort_unless($tp, 422, 'Fase no encontrada en este torneo.');

        // Validar que equipos pertenecen al torneo
        $tTeamIds = $tournament->teams()->pluck('teams.id')->toArray();
        foreach ($data['matches'] as $m) {
            if (!in_array($m['home_team_id'], $tTeamIds, true) || !in_array($m['away_team_id'], $tTeamIds, true)) {
                abort(422, 'Alguno de los equipos no pertenece al torneo.');
            }
        }

        $minRest = (int)($data['min_rest_minutes'] ?? 120); // descanso mínimo por equipo entre juegos (minutos)
        $config = $tournament->configuration;
        $matchDuration = ($config->game_time ?? 0) + ($config->time_between_games ?? 0) + 15 + 15; // minutos

        // Validaciones internas del lote: duplicados campo/hora y juegos del mismo equipo
        $fieldTimeSet = [];
        $teamDayMap = [];
        foreach ($data['matches'] as $m) {
            if ((int)$m['home_team_id'] === (int)$m['away_team_id']) {
                abort(422, 'Un equipo no puede enfrentarse a sí mismo.');
            }
            $key = $m['field_id'] . '|' . \Carbon\Carbon::parse($m['match_date'])->toDateString() . '|' . $m['match_time'];
            if (isset($fieldTimeSet[$key])) {
                abort(422, 'Hay partidos duplicados en el mismo campo y hora dentro de la solicitud.');
            }
            $fieldTimeSet[$key] = true;

            $dateStr = \Carbon\Carbon::parse($m['match_date'])->toDateString();
            foreach (['home_team_id','away_team_id'] as $side) {
                $teamId = (int)$m[$side];
                $teamDayMap[$teamId][$dateStr][] = $m['match_time'];
            }
        }

        // Validar descanso entre los partidos del lote para un mismo equipo
        foreach ($teamDayMap as $teamId => $dates) {
            foreach ($dates as $dateStr => $times) {
                sort($times);
                for ($i=1; $i<count($times); $i++) {
                    $prev = \Carbon\Carbon::createFromFormat('H:i', $times[$i-1]);
                    $curr = \Carbon\Carbon::createFromFormat('H:i', $times[$i]);
                    $diff = $prev->diffInMinutes($curr);
                    if ($diff < $minRest + $matchDuration) {
                        abort(422, sprintf('El equipo %d no cumple el descanso mínimo entre partidos el %s.', $teamId, $dateStr));
                    }
                }
            }
        }

        // Persistir partidos, validando colisiones de horario/campo y descanso contra juegos existentes
        $created = [];
        foreach ($data['matches'] as $m) {
            $date = \Carbon\Carbon::parse($m['match_date'])->toDateString();
            $time = $m['match_time'] . ':00';

            $exists = \App\Models\Game::where('field_id', $m['field_id'])
                ->whereDate('match_date', $date)
                ->where('match_time', $time)
                ->exists();
            if ($exists) {
                abort(422, 'Ya existe un partido programado en ese campo y hora.');
            }

            // Descanso con juegos ya existentes del torneo para cada equipo
            foreach (['home_team_id','away_team_id'] as $side) {
                $teamId = (int)$m[$side];
                $existing = \App\Models\Game::where('tournament_id', $tournament->id)
                    ->whereDate('match_date', $date)
                    ->where(function($q) use ($teamId){
                        $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
                    })
                    ->get(['match_time']);
                foreach ($existing as $g) {
                    $existingStart = \Carbon\Carbon::createFromFormat('H:i', substr($g->match_time,0,5));
                    $thisStart = \Carbon\Carbon::createFromFormat('H:i', substr($time,0,5));
                    $diff = abs($existingStart->diffInMinutes($thisStart));
                    if ($diff < $minRest + $matchDuration) {
                        abort(422, sprintf('El equipo %d no tiene el descanso mínimo respecto a otro partido programado el %s.', $teamId, $date));
                    }
                }
            }
            $locationId = \DB::table('fields')->where('id', $m['field_id'])->value('location_id');

            $created[] = \App\Models\Game::create([
                'tournament_id' => $tournament->id,
                'league_id' => auth()->user()->league_id,
                'tournament_phase_id' => $tp->id,
                'home_team_id' => (int)$m['home_team_id'],
                'away_team_id' => (int)$m['away_team_id'],
                'field_id' => (int)$m['field_id'],
                'location_id' => (int)$locationId,
                'match_date' => $date,
                'match_time' => $time,
                'round' => (int)($m['leg'] ?? 1),
                'status' => \App\Models\Game::STATUS_SCHEDULED,
            ]);
        }

        return response()->json([
            'message' => 'Llaves confirmadas y partidos agendados',
            'phase' => $tp->load('phase'),
            'data' => array_map(fn($g) => [
                'id' => $g->id,
                'home_team_id' => $g->home_team_id,
                'away_team_id' => $g->away_team_id,
                'match_date' => $g->match_date->toDateString(),
                'match_time' => $g->match_time,
                'field_id' => $g->field_id,
                'round' => $g->round,
            ], $created),
        ]);
    }
}
