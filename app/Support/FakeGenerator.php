<?php

namespace App\Support;

/**
 * Proxy object returned when the app asks for Faker\Generator.
 * It forwards method calls to the static Fake helper.
 */
class FakeGenerator extends \Faker\Generator
{
    public function __call(string $name, array $arguments)
    {
        if (method_exists(Fake::class, $name)) {
            return Fake::{$name}(...$arguments);
        }
        throw new \BadMethodCallException("Fake method '{$name}' is not implemented");
    }
}

