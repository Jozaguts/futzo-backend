<?php

namespace App\Providers;

use App\Listeners\AssignAdminRoleOnCheckoutListener;
use App\Listeners\PaymentIntentWebhookListener;
use App\Listeners\CreatePostCheckoutLoginListener;
use App\Listeners\ProgramSpecialFirstMonthScheduleListener;
use App\Listeners\SyncOwnerAndLeagueStatusListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        WebhookReceived::class => [
            AssignAdminRoleOnCheckoutListener::class,
            CreatePostCheckoutLoginListener::class,
            ProgramSpecialFirstMonthScheduleListener::class,
            SyncOwnerAndLeagueStatusListener::class,
            PaymentIntentWebhookListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}
