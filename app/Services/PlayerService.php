<?php

namespace App\Services;

use App\Http\Requests\PlayerStoreRequest;
use App\Services\Builders\PlayerBuilder;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class PlayerService
{
    protected PlayerBuilder $builder;

    public function __construct(PlayerBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws \Throwable
     */
    public function store($userData, $playerData): void
    {
        $this->builder
            ->setUserData($userData)
            ->setPlayerData($playerData)
            ->build();
    }

}
