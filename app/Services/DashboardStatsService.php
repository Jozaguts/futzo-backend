<?php

namespace App\Services;

use App\Models\Game;
use App\Models\LineupPlayer;
use App\Models\Player;
use App\Models\Team;
use http\Exception\InvalidArgumentException;

class DashboardStatsService
{
    public static function getTeamStats(string $period): array
    {
        return self::calculateStatsForPeriod($period);
    }

    private static function calculateStatsForPeriod(string $period): array
    {
        [$startCurrent, $endCurrent, $startPrevious, $endPrevious] = self::getDateRangeForPeriod($period);

        // 1. Calcular `registeredTeams`
        $registeredTeamsCurrent = Team::whereBetween('created_at', [$startCurrent, $endCurrent])
            ->whereHas('leagues', fn($q) => $q->where('leagues.id', auth()->user()->league_id))
            ->count();
        $registeredTeamsPrevious = Team::whereBetween('created_at', [$startPrevious, $endPrevious])
            ->whereHas('leagues', fn($q) => $q->where('leagues.id', auth()->user()->league_id))
            ->count();
        $registeredTeamsStats = self::calculateStatsValues($registeredTeamsCurrent, $registeredTeamsPrevious);
        $registeredTeamsStats['total'] = Team::whereHas('leagues', static fn($q) => $q->where('leagues.id', auth()->user()->league_id))
            ->count();
        $registeredTeamsStats['dailyData'] = self::getDailyData(Team::class, 'created_at', $period, $startCurrent, $endCurrent);


        // 2. Calcular `activePlayers`
        // Usamos Game en lugar de Player para calcular los jugadores activos en juegos.
        $activePlayersCurrent = Player::whereHas('lineupPlayers', static function ($query) use ($startCurrent, $endCurrent) {
            $query->whereBetween('created_at', [$startCurrent, $endCurrent]);
        })->whereHas('team.leagues', fn($q) => $q->where('leagues.id', auth()->user()->league_id))
            ->count();
        $activePlayersPrevious = Player::whereHas('lineupPlayers', static function ($query) use ($startPrevious, $endPrevious) {
            $query->whereBetween('created_at', [$startPrevious, $endPrevious]);
        })->whereHas('team.leagues', fn($q) => $q->where('leagues.id', auth()->user()->league_id))
            ->count();
        $activePlayersStats = self::calculateStatsValues($activePlayersCurrent, $activePlayersPrevious);
        $activePlayersStats['dailyData'] = self::getDailyData(LineupPlayer::class, 'created_at', $period, $startCurrent, $endCurrent, null);

        // 3. Calcular `completedGames`
        $completedGamesCurrent = Game::whereBetween('created_at', [$startCurrent, $endCurrent])
            ->where('status', Game::STATUS_COMPLETED)
            ->where('league_id', auth()->user()->league_id)
            ->count();
        $completedGamesPrevious = Game::whereBetween('created_at', [$startPrevious, $endPrevious])
            ->where('status', Game::STATUS_COMPLETED)
            ->where('league_id', auth()->user()->league_id)
            ->count();
        $completedGamesStats = self::calculateStatsValues($completedGamesCurrent, $completedGamesPrevious);
        $completedGamesStats['dailyData'] = self::getDailyData(Game::class, 'created_at', $period, $startCurrent, $endCurrent, null, ['status' => Game::STATUS_COMPLETED]);
        $label = self::getLabel($period);
        $registeredTeamsStats['label'] = $label;
        $activePlayersStats['label'] = $label;
        $completedGamesStats['label'] = $label;
        return [
            'registeredTeams' => $registeredTeamsStats,
            'activePlayers' => $activePlayersStats,
            'completedGames' => $completedGamesStats,
        ];
    }

    private static function getLabel(string $period): string
    {
        return match ($period) {
            'last24Hrs' => ' últimas 24 hrs',
            'lastWeek' => ' última semana',
            'lastMonth' => ' último mes',
            'lastYear' => ' últimos 12 meses'
        };
    }

    private static function getDailyData($model, $dateColumn, $period, $start, $end, $relation = null, $conditions = []): array
    {
        $query = (new $model);

        // Aplica condiciones adicionales si existen
        if ($conditions) {
            foreach ($conditions as $column => $value) {
                $query = $query->where($column, $value);
            }
        }

        // Configura la relación si se especifica (como `players` en `Game`)
        if ($relation) {
            $query = $query->whereHas($relation, function ($q) use ($dateColumn, $start, $end, $relation) {
                // Asegúrate de que el dateColumn esté cualificado con la tabla correcta
                $q->whereBetween($relation . '.' . $dateColumn, [$start, $end]);
            });
        } else {
            // Cualifica dateColumn en el modelo principal
            $query = $query->whereBetween((new $model)->getTable() . '.' . $dateColumn, [$start, $end]);
        }

        switch ($period) {
            case 'last24Hrs':
                $data = $query->selectRaw('HOUR(' . (new $model)->getTable() . '.' . $dateColumn . ') as time_unit, COUNT(*) as count')
                    ->groupBy('time_unit')
                    ->orderBy('time_unit')
                    ->pluck('count', 'time_unit')
                    ->toArray();
                $data = array_replace(array_fill(0, 24, 0), $data);
                break;

            case 'lastWeek':
                $data = $query->selectRaw('DAYOFWEEK(' . (new $model)->getTable() . '.' . $dateColumn . ') as time_unit, COUNT(*) as count')
                    ->groupBy('time_unit')
                    ->orderBy('time_unit')
                    ->pluck('count', 'time_unit')
                    ->toArray();
                $data = array_replace(array_fill(1, 7, 0), $data);
                break;

            case 'lastMonth':
                $daysInMonth = now()->daysInMonth;
                $data = $query->selectRaw('DAY(' . (new $model)->getTable() . '.' . $dateColumn . ') as time_unit, COUNT(*) as count')
                    ->groupBy('time_unit')
                    ->orderBy('time_unit')
                    ->pluck('count', 'time_unit')
                    ->toArray();
                $data = array_replace(array_fill(1, $daysInMonth, 0), $data);
                break;

            case 'lastYear':
                $data = $query->selectRaw('MONTH(' . (new $model)->getTable() . '.' . $dateColumn . ') as time_unit, COUNT(*) as count')
                    ->groupBy('time_unit')
                    ->orderBy('time_unit')
                    ->pluck('count', 'time_unit')
                    ->toArray();
                $data = array_replace(array_fill(1, 12, 0), $data);
                break;

            default:
                throw new InvalidArgumentException("Periodo no válido");
        }

        return array_values($data);
    }

    private static function getDateRangeForPeriod(string $period): array
    {
        return match ($period) {
            'last24Hrs' => [
                now()->subDay(),
                now(),
                now()->subDays(2),
                now()->subDay()
            ],
            'lastWeek' => [
                now()->startOfWeek(),
                now(),
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ],
            'lastMonth' => [
                now()->startOfMonth(),
                now(),
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ],
            'lastYear' => [
                now()->startOfYear(),
                now(),
                now()->subYear()->startOfYear(),
                now()->subYear()->endOfYear()
            ],
            default => throw new InvalidArgumentException("Periodo no válido"),
        };
    }

    private static function calculateStatsValues(int $current, int $previous): array
    {
        // Calcula el porcentaje de crecimiento considerando el caso donde `previous` es 0

        return [
            'total' => $current,
            'current' => $current
        ];
    }
}
