<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Set Carbon locale to application locale
        Carbon::setLocale(config('app.locale'));

        // Set PHP locale for date/time translations where available
        setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'French_France');


    }
}
