<?php

namespace App\Providers;

use Carbon\Carbon;
use Essa\APIToolKit\Exceptions\Handler;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExceptionHandler::class, Handler::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force Carbon to serialize dates using app timezone instead of UTC
        Carbon::serializeUsing(function ($carbon) {
            return $carbon
                ->setTimezone(config("app.timezone"))
                ->toIso8601String();
        });
    }
}
