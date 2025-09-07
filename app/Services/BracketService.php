<?php

namespace App\Services;

use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BracketService
{
    public function groupStandings(int $tournamentId, ?int $phaseId = null): array
    {
        // Detectar fase de grupos si no se pasa
        if (is_null($phaseId)) {
            $phaseId = DB::table('tournament_phases')
                ->join('phases','phases.id','=','tournament_phases.phase_id')
                ->where('tournament_phases.tournament_id', $tournamentId)
                ->where('phases.name','Fase de grupos')
                ->value('tournament_phases.id');
        }
        if (is_null($phaseId)) {
            throw new RuntimeException('No se encontró la fase de grupos.');
        }

        // Standings + group_key + team info
        $rows = DB::table('standings')
            ->join('teams','teams.id','=','standings.team_id')
            ->join('team_tournament','team_tournament.id','=','standings.team_tournament_id')
            ->select([
                'standings.*',
                'team_tournament.group_key',
                'teams.name as team_name',
                DB::raw("COALESCE(teams.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(teams.name,' ', '+'))) as team_image"),
            ])
            ->where('standings.tournament_id', $tournamentId)
            ->where('standings.tournament_phase_id', $phaseId)
            ->orderBy('team_tournament.group_key')
            ->orderBy('standings.rank')
            ->get();

        return $rows->groupBy('group_key')->map(function ($groupRows, $gk) {
            return [
                'group' => $gk,
                'teams' => $groupRows->map(function ($r) {
                    return [
                        'team_id' => $r->team_id,
                        'team_name' => $r->team_name,
                        'team_image' => $r->team_image,
                        'rank' => (int)$r->rank,
                        'points' => (int)$r->points,
                        'matches_played' => (int)$r->matches_played,
                        'wins' => (int)$r->wins,
                        'draws' => (int)$r->draws,
                        'losses' => (int)$r->losses,
                        'goals_for' => (int)$r->goals_for,
                        'goals_against' => (int)$r->goals_against,
                        'goal_difference' => (int)$r->goal_difference,
                    ];
                })->values(),
            ];
        })->values()->toArray();
    }

    public function previewSeeding(Tournament $tournament, string $phaseName): array
    {
        $formatId = (int)$tournament->configuration->tournament_format_id;
        $targetTeams = match($phaseName) {
            'Octavos de Final' => 16,
            'Cuartos de Final' => 8,
            'Semifinales' => 4,
            'Final' => 2,
            default => 8,
        };

        $qualifiers = [];
        $meta = [];
        if ($formatId === 5) { // Grupos y Eliminatoria
            $groupPhaseId = DB::table('tournament_phases')
                ->join('phases','phases.id','=','tournament_phases.phase_id')
                ->where('tournament_phases.tournament_id', $tournament->id)
                ->where('phases.name','Fase de grupos')
                ->value('tournament_phases.id');
            if (!$groupPhaseId) {
                throw new RuntimeException('No existe la fase de grupos para calcular clasificados.');
            }

            $gc = $tournament->groupConfiguration;
            if (!$gc) {
                throw new RuntimeException('No hay configuración de grupos.');
            }

            $rows = DB::table('standings')
                ->join('team_tournament','team_tournament.id','=','standings.team_tournament_id')
                ->join('teams','teams.id','=','standings.team_id')
                ->where('standings.tournament_id', $tournament->id)
                ->where('standings.tournament_phase_id', $groupPhaseId)
                ->select(['standings.*','team_tournament.group_key','teams.name as team_name','teams.image as team_image'])
                ->orderBy('team_tournament.group_key')
                ->orderBy('standings.rank')
                ->get();
            if ($rows->isEmpty()) {
                throw new RuntimeException('No hay standings de grupos calculados.');
            }
            $byGroup = $rows->groupBy('group_key');
            $thirdCandidates = [];
            foreach ($byGroup as $gk => $gr) {
                $topN = $gr->sortBy('rank')->take($gc->advance_top_n);
                foreach ($topN as $r) { $qualifiers[] = $r; }
                if ($gc->include_best_thirds) {
                    $third = $gr->firstWhere('rank', $gc->advance_top_n + 1);
                    if ($third) { $thirdCandidates[] = $third; }
                }
            }
            if ($gc->include_best_thirds && $gc->best_thirds_count) {
                usort($thirdCandidates, function($a,$b){
                    return ($b->points <=> $a->points) ?: ($b->goal_difference <=> $a->goal_difference) ?: ($b->goals_for <=> $a->goals_for);
                });
                $best = array_slice($thirdCandidates, 0, (int)$gc->best_thirds_count);
                foreach ($best as $r) { $qualifiers[] = $r; }
            }
            // Orden global por métricas de grupo
            usort($qualifiers, function($a,$b){
                return ($b->points <=> $a->points) ?: ($b->goal_difference <=> $a->goal_difference) ?: ($b->goals_for <=> $a->goals_for);
            });
            $qualifiers = array_slice($qualifiers, 0, $targetTeams);
            $meta['source'] = 'group_standings';
        } elseif ($formatId === 2) { // Liga + Eliminatoria
            $tablePhaseId = DB::table('tournament_phases')
                ->join('phases','phases.id','=','tournament_phases.phase_id')
                ->where('tournament_phases.tournament_id', $tournament->id)
                ->where('phases.name','Tabla general')
                ->value('tournament_phases.id');
            if (!$tablePhaseId) {
                throw new RuntimeException('No existe la fase "Tabla general".');
            }
            $rows = DB::table('standings')
                ->join('teams','teams.id','=','standings.team_id')
                ->where('standings.tournament_id', $tournament->id)
                ->where('standings.tournament_phase_id', $tablePhaseId)
                ->orderBy('rank')
                ->limit($targetTeams)
                ->get(['standings.*','teams.name as team_name','teams.image as team_image']);
            $qualifiers = $rows->all();
            $meta['source'] = 'table_standings';
        } else {
            throw new RuntimeException('Formato no soportado para previsualización de llaves.');
        }

        // Construir respuesta con seeds y pares 1-N
        $qualifiers = array_values(array_map(function($r, $idx){
            return [
                'seed' => $idx + 1,
                'team_id' => $r->team_id,
                'team_name' => $r->team_name ?? null,
                'team_image' => $r->team_image ?? null,
                'points' => (int)$r->points,
                'goal_difference' => (int)$r->goal_difference,
                'goals_for' => (int)$r->goals_for,
                'group_key' => $r->group_key ?? null,
            ];
        }, $qualifiers, array_keys($qualifiers)));

        $pairs = [];
        $n = count($qualifiers);
        for ($i=0; $i < intdiv($n,2); $i++) {
            $pairs[] = [
                'home_seed' => $qualifiers[$i]['seed'],
                'home' => $qualifiers[$i],
                'away_seed' => $qualifiers[$n-1-$i]['seed'],
                'away' => $qualifiers[$n-1-$i],
            ];
        }

        return [
            'phase' => $phaseName,
            'target_teams' => $targetTeams,
            'source' => $meta['source'] ?? null,
            'qualifiers' => $qualifiers,
            'pairs' => $pairs,
            'rules' => optional($tournament->tournamentPhases()->whereHas('phase', fn($q)=>$q->where('name',$phaseName))->first())->rules,
        ];
    }
}

