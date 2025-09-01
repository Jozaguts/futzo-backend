<?php

// Polyfill for Faker in production: only defines classes if the real Faker is absent.

namespace Faker;

if (!\class_exists(\Faker\Factory::class)) {
    interface Generator {}

    class Factory
    {
        public static function create(?string $locale = null): \Faker\Generator
        {
            return new \App\Support\FakeGenerator();
        }
    }
}
