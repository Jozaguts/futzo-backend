<?php

namespace Tests;

use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait InitUser
{
    public User|null $user = null;

    public function initUser(): User
    {
        $this->user = User::factory()->create();

        Sanctum::actingAs($this->user);
        $this->user->assignRole('super administrador');
        if ($this->user->league_id) {
            $this->user->league_id = null;
        }
        $this->user->save();
        return $this->user;
    }

    public function addLeague(): User
    {
        $this->user->league_id = 1;
        $this->user->save();
        return $this->user;
    }
}
