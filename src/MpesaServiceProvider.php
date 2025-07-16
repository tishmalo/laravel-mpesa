<?php

namespace Tish\LaravelMpesa;

use Illuminate\Support\ServiceProvider;


class MpesaServiceProvider extends ServiceProvider
{
    /**
     * Register services (bind stuff in container).
     */
    public function register()
    {
        $this->app->singleton('mpesa', function ($app) {
            return new MpesaService();
        });
    
        $this->mergeConfigFrom(
            __DIR__.'/../config/mpesa.php', 'mpesa'
        );
    }
    
    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/mpesa.php' => config_path('mpesa.php'),
        ], 'mpesa-config');
    
        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'mpesa-migrations');
    
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
    
}