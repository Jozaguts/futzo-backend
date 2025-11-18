<?php

use App\Http\Middleware\AppendLeagueHeaderMiddleware;
use App\Http\Middleware\CheckTeamCanRegisterMiddleware;
use App\Http\Middleware\CheckTournamentCanRegisterMiddleware;
use App\Http\Middleware\CheckTournamentEliminationPhaseMiddleware;
use App\Http\Middleware\CheckUserHasRole;
use App\Http\Middleware\CustomEnsureEmailIsVerified;
use App\Http\Middleware\EnsureCheckoutEligibilityMiddleware;
use App\Http\Middleware\EnsureOperationalForBilling;
use App\Http\Middleware\EnsureTournamentQuota;
use App\Http\Middleware\HasNotLeagueMiddleware;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\ValidateSignature;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified' => CustomEnsureEmailIsVerified::class,
            'hasRole' => CheckUserHasRole::class,
            'ensureUserOwnsProfile' => \App\Http\Middleware\EnsureUserOwnsProfile::class,
            'hasNotLeague' => HasNotLeagueMiddleware::class,
            'team.can_register_player' => CheckTeamCanRegisterMiddleware::class,
            'tournament.can_register_team' => CheckTournamentCanRegisterMiddleware::class,
            'tournament.registration_phase_open' => CheckTournamentEliminationPhaseMiddleware::class,
            'checkout.eligibility' => EnsureCheckoutEligibilityMiddleware::class,
            'billing.operational' => EnsureOperationalForBilling::class,
            'tournaments.quota' => EnsureTournamentQuota::class,
        ]);

        $middleware->appendToGroup('web', [
            AppendLeagueHeaderMiddleware::class,
        ]);

        $middleware->appendToGroup('api', [
            AppendLeagueHeaderMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })->create();
