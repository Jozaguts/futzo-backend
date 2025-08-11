<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Standing;
use Carbon\Carbon;
use Throwable;

class StandingsService
{
    /**
     * Recalcula los standings para un torneo + fase (o fase NULL)
     *
     * @param int $tournamentId
     * @param int|null $tournamentPhaseId
     * @param int|null $triggeringGameId // el game que disparó el recalculo
     * @throws Throwable
     */
    public function recalculateStandingsForPhase(int $tournamentId, ?int $tournamentPhaseId = null, ?int $triggeringGameId = null): void
    {
        DB::transaction(function () use ($tournamentId, $tournamentPhaseId, $triggeringGameId) {

            // 1) Mapear team_tournament (team_id => team_tournament_id)
            $teamTournamentMap = DB::table('team_tournament')
                ->where('tournament_id', $tournamentId)
                ->pluck('id', 'team_id') // returns [team_id => team_tournament_id]
                ->toArray();

            // 2) Inicializar estructura in-memory para stats
            $stats = [];
            foreach ($teamTournamentMap as $teamId => $teamTournamentId) {
                $stats[$teamId] = [
                    'team_id' => $teamId,
                    'team_tournament_id' => $teamTournamentId,
                    'matches_played' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                    'points' => 0,
                    'results' => [], // array de 'W','D','L' en orden cronológico
                ];
            }

            // 3) Obtener partidos completados de la fase, orden cronológico (fecha,hora,id)
            $gamesQuery = DB::table('games')
                ->where('tournament_id', $tournamentId)
                ->where('status', 'completado');

            if (is_null($tournamentPhaseId)) {
                $gamesQuery->whereNull('tournament_phase_id');
            } else {
                $gamesQuery->where('tournament_phase_id', $tournamentPhaseId);
            }

            $games = $gamesQuery
                ->orderBy('match_date')
                ->orderBy('match_time')
                ->orderBy('id')
                ->get();

            // 4) Procesar cada juego (cronológico) y actualizar estructuras
            foreach ($games as $g) {
                $home = (int) $g->home_team_id;
                $away = (int) $g->away_team_id;
                $hg = (int) $g->home_goals;
                $ag = (int) $g->away_goals;

                // Asegurarnos que el equipo exista en stats (por si no tenía team_tournament)
                foreach ([$home, $away] as $teamId) {
                    if (!isset($stats[$teamId])) {
                        $teamTournamentId = DB::table('team_tournament')
                            ->where('tournament_id', $tournamentId)
                            ->where('team_id', $teamId)
                            ->value('id');

                        $stats[$teamId] = [
                            'team_id' => $teamId,
                            'team_tournament_id' => $teamTournamentId,
                            'matches_played' => 0,
                            'wins' => 0,
                            'draws' => 0,
                            'losses' => 0,
                            'goals_for' => 0,
                            'goals_against' => 0,
                            'points' => 0,
                            'results' => [],
                        ];
                    }
                }

                // Home update
                $stats[$home]['matches_played']++;
                $stats[$home]['goals_for'] += $hg;
                $stats[$home]['goals_against'] += $ag;

                // Away update
                $stats[$away]['matches_played']++;
                $stats[$away]['goals_for'] += $ag;
                $stats[$away]['goals_against'] += $hg;

                // Resultados y puntos
                if ($hg > $ag) {
                    $stats = $this->updateStats($stats, $home, $away);
                } elseif ($hg === $ag) {
                    $stats[$home]['draws']++;
                    ++$stats[$home]['points'];
                    $stats[$home]['results'][] = 'D';

                    $stats[$away]['draws']++;
                    ++$stats[$away]['points'];
                    $stats[$away]['results'][] = 'D';
                } else {
                    $stats = $this->updateStats($stats, $away, $home);
                }
            }

            // 5) Calcular GD, last_5 y persistir (updateOrCreate)
            // Obtener league_id del torneo (si/cuando lo necesites)
            $leagueId = DB::table('tournaments')->where('id', $tournamentId)->value('league_id');

            foreach ($stats as $teamId => $s) {
                $gd = $s['goals_for'] - $s['goals_against'];

                // Construir last_5 en el mismo formato: 5 caracteres, '-' para espacios vacíos,
                // orden: más antiguo a la izquierda, último (más reciente) a la derecha.
                $results = $s['results'];
                $last5Arr = array_slice($results, -5);
                $pad = max(0, 5 - count($last5Arr));
                $last5Str = str_repeat('-', $pad) . implode('', $last5Arr);

                // upsert
                Standing::updateOrCreate(
                    [
                        'team_id' => $teamId,
                        'team_tournament_id' => $s['team_tournament_id'],
                        'tournament_phase_id' => $tournamentPhaseId,
                    ],
                    [
                        'matches_played' => $s['matches_played'],
                        'wins' => $s['wins'],
                        'draws' => $s['draws'],
                        'losses' => $s['losses'],
                        'goals_for' => $s['goals_for'],
                        'goals_against' => $s['goals_against'],
                        'goal_difference' => $gd,
                        'points' => $s['points'],
                        'last_5' => $last5Str,
                        'tournament_id' => $tournamentId,
                        'league_id' => $leagueId,
                        'updated_after_game_id' => $triggeringGameId,
                    ]
                );
            }

            // 6) Recalcular ranks (standard competition ranking)
            $this->recalculateRanks($tournamentId, $tournamentPhaseId);
        });
    }

    /**
     * Recalcula y persiste el campo `rank` para una fase/tournament
     * utiliza: pts DESC, gd DESC, gf DESC, fair_play_points ASC (menor mejor).
     */
    protected function recalculateRanks(int $tournamentId, ?int $tournamentPhaseId = null): void
    {
        $query = Standing::where('tournament_id', $tournamentId);
        if (is_null($tournamentPhaseId)) {
            $query->whereNull('tournament_phase_id');
        } else {
            $query->where('tournament_phase_id', $tournamentPhaseId);
        }

        $rows = $query
            ->orderByDesc('points')
            ->orderByDesc('goal_difference')
            ->orderByDesc('goals_for')
            ->orderBy('fair_play_points') // lower is better
            ->get();

        $position = 0;
        $prevKey = null;
        $currentRank = 0;

        foreach ($rows as $row) {
            $position++;

            $key = "{$row->points}|{$row->goal_difference}|{$row->goals_for}|{$row->fair_play_points}";

            if ($key === $prevKey) {
                // empate perfecto según los criterios usados => mismo rank
                $row->rank = $currentRank;
            } else {
                // nuevo bloque => el rank es la posición (esto implementa "competition ranking" 1,2,2,4)
                $currentRank = $position;
                $row->rank = $currentRank;
                $prevKey = $key;
            }

            $row->saveQuietly();
        }
    }

    /**
     * @param array $stats
     * @param int $away
     * @param int $home
     * @return array
     */
    public function updateStats(array $stats, int $away, int $home): array
    {
        $stats[$away]['wins']++;
        $stats[$away]['points'] += 3;
        $stats[$away]['results'][] = 'W';

        $stats[$home]['losses']++;
        $stats[$home]['results'][] = 'L';
        return $stats;
    }
}
