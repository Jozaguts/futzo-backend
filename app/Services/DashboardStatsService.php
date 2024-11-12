<?php

namespace App\Services;

use App\Models\Game;
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
        // Obtener las fechas de inicio y fin del período actual y el anterior
        [$startCurrent, $endCurrent, $startPrevious, $endPrevious] = self::getDateRangeForPeriod($period);

        // 1. Calcular `registeredTeams` (equipos registrados)
        $registeredTeamsCurrent = Team::whereBetween('teams.created_at', [$startCurrent, $endCurrent])->count();
        $registeredTeamsPrevious = Team::whereBetween('teams.created_at', [$startPrevious, $endPrevious])->count();
        $registeredTeamsStats = self::calculateStatsValues($registeredTeamsCurrent, $registeredTeamsPrevious);


        // 2. Calcular `activePlayers` (jugadores que participaron en un partido)
        $activePlayersCurrent = Player::whereHas('players.games', function ($query) use ($startCurrent, $endCurrent) {
            $query->whereBetween('games.created_at', [$startCurrent, $endCurrent]);
        })->count();
        $activePlayersPrevious = Player::whereHas('games', function ($query) use ($startPrevious, $endPrevious) {
            $query->whereBetween('games.created_at', [$startPrevious, $endPrevious]);
        })->count();
        $activePlayersStats = self::calculateStatsValues($activePlayersCurrent, $activePlayersPrevious);

        // 3. Calcular `completedGames` (partidos completados)
        $completedGamesCurrent = Game::whereBetween('games.created_at', [$startCurrent, $endCurrent])
            ->where('status', Game::STATUS_COMPLETED)
            ->count();
        $completedGamesPrevious = Game::whereBetween('created_at', [$startPrevious, $endPrevious])
            ->where('status', Game::STATUS_COMPLETED)
            ->count();
        $completedGamesStats = self::calculateStatsValues($completedGamesCurrent, $completedGamesPrevious);

        return [
            'registeredTeams' => $registeredTeamsStats,
            'activePlayers' => $activePlayersStats,
            'completedGames' => $completedGamesStats,
        ];
    }

    private static function getDateRangeForPeriod(string $period): array
    {
        return match ($period) {
            'last24Hrs' => [
                now()->subDay(), now(),
                now()->subDays(2), now()->subDay()
            ],
            'lastWeek' => [
                now()->startOfWeek(), now(),
                now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()
            ],
            'lastMonth' => [
                now()->startOfMonth(), now(),
                now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()
            ],
            'lastYear' => [
                now()->startOfYear(), now(),
                now()->subYear()->startOfYear(), now()->subYear()->endOfYear()
            ],
            default => throw new InvalidArgumentException("Periodo no válido"),
        };
    }

    private static function calculateStatsValues(int $current, int $previous): array
    {
        $percentage = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 2)
            : ($current > 0 ? 100 : 0); // Si el anterior es 0, asumimos un 100% de incremento

        return [
            'total' => $current,
            'percentage' => $percentage
        ];
    }
}
