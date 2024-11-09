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
     */
    public function store(PlayerStoreRequest $request): void
    {
        $this->builder
            ->setUserData($request->userFormData())
            ->setPlayerData($request->playerFormData())
            ->build();
    }

}
