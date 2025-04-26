<?php

namespace App\Services;

use App\Models\Game;

class RoundStatusService
{
    public static function getRoundStatus(int $tournamentId, int $round): string
    {
        $statuses = Game::where('tournament_id', $tournamentId)
            ->where('round', $round)
            ->pluck('status')
            ->unique();

        if ($statuses->count() === 1 && $statuses->first() === Game::STATUS_SCHEDULED) {
            return 'programado';
        }

        if ($statuses->contains(Game::STATUS_IN_PROGRESS)) {
            return 'en_progreso';
        }

        if ($statuses->contains(Game::STATUS_COMPLETED) && $statuses->count() === 1) {
            return 'completado';
        }

        if ($statuses->contains(Game::STATUS_COMPLETED) && $statuses->count() > 1) {
            return 'parcialmente jugada';
        }

        if ($statuses->every(fn($s) => in_array($s, [Game::STATUS_COMPLETED, Game::STATUS_CANCELED]))) {
            return 'cancelado';
        }

        return 'mixta';
    }
}
