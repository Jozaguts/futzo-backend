<?php

namespace App\Support;

class FakeFactory
{
    public static function create(): FakeGenerator
    {
        return new FakeGenerator();
    }
}