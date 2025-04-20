<?php

namespace App\Providers;

use App\Broadcasting\WhatsAppChannel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        setlocale(LC_ALL, 'es_MX', 'es', 'ES', 'es_MX.utf8');
        JsonResource::withoutWrapping();
        Schema::defaultStringLength(191);
        ResetPassword::createUrlUsing(static function (User $user, string $token) {
            return config('app.frontend_url') . '/login?reset_password=1&token=' . $token . '&email=' . $user->getEmailForPasswordReset();
        });

        Notification::extend('whatsapp', function ($app) {
            return new WhatsAppChannel;
        });
    }
}
