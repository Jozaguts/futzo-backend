<?php

namespace App\Services;

use App\Models\League;
use App\Models\User;

class OnboardingService
{
    /**
     * Compute onboarding steps for the given user.
     * Steps are derived from current data so they auto-update if entities are created/deleted.
     */
    public function stepsFor(User $user): array
    {
        $league = $user->league;
        $hasLeague = (bool) $league;
        $hasLocation = $hasLeague && $this->leagueHasLocations($league);
        $hasField = $hasLeague && $this->leagueHasFields($league);

        $steps = [
            [
                'id' => 'create_league',
                'title' => 'Crea tu primera liga',
                'description' => 'El primer paso para organizar tus partidos.',
                'done' => $hasLeague,
                'link' => '/bienvenido',
                'blocking' => true,
            ],
            [
                'id' => 'create_location',
                'title' => 'Crea tu primera ubicación',
                'description' => 'Dónde estan tus campos de juego.',
                'done' => $hasLocation,
                'link' => '/ubicaciones',
                'blocking' => true,
                'requires' => ['create_league'],
            ],
            [
                'id' => 'create_field',
                'title' => 'Configura tus canchas',
                'description' => 'Donde se jugan tus partidos.',
                'done' => $hasField,
                'link' => '/ubicaciones',
                'blocking' => true,
                'requires' => ['create_location'],
            ],
        ];

        $next = null;
        foreach ($steps as $s) {
            if (!$s['done']) { $next = $s['id']; break; }
        }

        // Allowed paths based on the next required step.
        $allowed = ['/']; // index siempre permitido
        if ($next === 'create_league') {
            // Crear liga: lo gestiona otro middleware hacia /bienvenido.
            // Aquí no añadimos /bienvenido para no colisionar; se permite permanecer en '/'.
        } elseif (in_array($next, ['create_location','create_field'], true)) {
            // Ubicaciones/canchas se gestionan en /ubicaciones (frontend)
            $allowed[] = '/ubicaciones';
        }

        return [
            'steps' => $steps,
            'next' => $next,
            'all_done' => $next === null,
            'allowed_paths' => $allowed,
        ];
    }

    private function leagueHasLocations(League $league): bool
    {
        return $league->locations()->exists();
    }

    private function leagueHasFields(League $league): bool
    {
        return $league->fields()->exists();
    }
}
