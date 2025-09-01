<?php

namespace App\Services\Builders;

use App\Contracts\IPlayerBuilder;
use App\Events\RegisteredPlayer;
use App\Models\Category;
use App\Models\League;
use App\Models\Player;
use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use Http\Client\Common\Exception\BatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class PlayerBuilder implements IPlayerBuilder
{
    protected array $userData = [];
    protected array $playerData = [];
    protected string $temporaryPassword;
    protected User $user;
    protected Player $player;

    public function setUserData(array $data): static
    {
        $this->userData = $data;
        return $this;
    }

    public function setPlayerData(array $data): static
    {
        $this->playerData = $data;
        return $this;
    }

    public function setTemporaryPassword(): static
    {
        $this->temporaryPassword = str()->random(8);
        $this->userData['password'] = $this->temporaryPassword;
        return $this;
    }

    public function createUser(): static
    {
        $this->user = User::createQuietly($this->userData);
        $this->user->assignRole('jugador');
        $league_id = request()->header('X-League-Id');
        $this->user->league()->associate( League::find($league_id));
        $this->user->saveQuietly();

        return $this;
    }

    public function createPlayer(): static
    {
        $this->player = $this->user->players()->create($this->playerData);
        if (isset($this->playerData['team_id'])) {
            $team = Team::find($this->playerData['team_id']);
            $this->player->team()->associate($team);

        }
        if (isset($this->playerData['position_id'])) {
            $position = Position::find($this->playerData['position_id']);
            $this->player->position()->associate($position);

        }
        if (isset($this->playerData['category_id'])) {
            $category = Category::find($this->playerData['category_id']);
            $this->player->category()->associate($category);

        }
        $this->player->saveQuietly();
        return $this;
    }

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function attachImageIfPresent(): static
    {
        if (request()->hasFile('basic.image')) {
            $url = $this->user
                ->addMedia($this->userData['image'])
                ->toMediaCollection('image')
                ->getUrl();
            $this->user->update(['image' => $url]);
        }
        return $this;
    }

    public function dispatchEvent(): static
    {
        event(new RegisteredPlayer($this->user, $this->userData['password']));
        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function build(): static
    {
        return DB::transaction(function () {
            return $this->setTemporaryPassword()
                ->createUser()
                ->createPlayer()
                ->attachImageIfPresent()
                ->dispatchEvent();
        });

    }
}
