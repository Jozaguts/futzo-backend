<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tournament;
use Illuminate\Auth\Access\Response;

class TournamentPolicy
{
    public function generateTournamentSchedule(User $user, Tournament $tournament): bool
    {
        if ($user->hasRole([ 'super administrador'])){
            return true;
        }
        if($user->hasRole([ 'administrador'])){
           return $user->league_id === $tournament->league_id;
        }
        return false;

    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tournament $tournament): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tournament $tournament): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tournament $tournament): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tournament $tournament): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tournament $tournament): bool
    {
        //
    }
}
