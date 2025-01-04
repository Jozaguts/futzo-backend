<?php

namespace App\Providers;

use App\Broadcasting\WhatsAppChannel;
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
		JsonResource::withoutWrapping();
		Schema::defaultStringLength(191);

		Notification::extend('whatsapp', function ($app) {
			return new WhatsAppChannel;
		});
	}
}
