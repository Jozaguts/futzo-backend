<?php

namespace App\Contracts;

interface IPlayerBuilder
{
    public function setUserData(array $data): static;

    public function setPlayerData(array $data): static;

    public function setTemporaryPassword(): static;

    public function createUser(): static;

    public function createPlayer(): static;

    public function attachImageIfPresent(): static;

    public function dispatchEvent(): static;

    public function build(): static;
}
