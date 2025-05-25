<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Database\Seeders\ActionsTableSeeder;
use Database\Seeders\CategoriesTableSeeder;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\CouponsTableSeeder;
use Database\Seeders\FootballTypesTableSeeder;
use Database\Seeders\LeaguesTableSeeder;
use Database\Seeders\PositionsTableSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;
use Tests\InitUser;
use Tests\TestCase;

uses(
    TestCase::class,
    RefreshDatabase::class,
    InitUser::class,
)
    ->beforeEach(function () {
        $this->seed([
            RolesTableSeeder::class,
            LeaguesTableSeeder::class,
            FootballTypesTableSeeder::class,
            CountriesSeeder::class,
            PositionsTableSeeder::class,
            CategoriesTableSeeder::class,
            ActionsTableSeeder::class,
        ]);
        $this->app
            ->make(PermissionRegistrar::class)
            ->forgetCachedPermissions();
        $this->user = $this->initUser();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
