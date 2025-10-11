<?php

namespace App\Support;

use App\Enums\FootballTypeId;

class MatchDuration
{
    private const int TRADITIONAL_BUFFER_MINUTES = 30; // 15 min de descanso + 15 min de contingencia

    /**
     * Calcula la duración total en minutos de un partido considerando descanso admin y buffers.
     */
    public static function minutes(?object $config, int $fallback = 0): int
    {
        if (is_null($config)) {
            return $fallback;
        }

        $gameTime = (int)($config->game_time ?? 0);
        $adminGap = (int)($config->time_between_games ?? 0);
        $buffer = self::bufferForFootballType($config->football_type_id ?? null);

        $total = $gameTime + $adminGap + $buffer;

        if ($total === 0 && $fallback > 0) {
            return $fallback;
        }

        return $total;
    }

    /**
     * Determina el buffer a aplicar según el tipo de fútbol.
     */
    public static function bufferForFootballType(null|int $footballTypeId): int
    {
        return (int)$footballTypeId === FootballTypeId::TraditionalFootball->value
            ? self::TRADITIONAL_BUFFER_MINUTES
            : 0;
    }
}
