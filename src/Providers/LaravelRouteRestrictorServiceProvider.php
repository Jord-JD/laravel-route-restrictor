<?php

namespace JordJD\LaravelRouteRestrictor\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Laravel Route Restrictor package
 *
 * @author Jordan Hall <jordan.hall@rapidweb.biz>
 */
class LaravelRouteRestrictorServiceProvider extends ServiceProvider
{
    /**
    * Perform post-registration booting of services.
    *
    * @return void
    */
    public function boot()
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/laravel-route-restrictor.php' => config_path('laravel-route-restrictor.php'),
        ]);
    }

    /**
    * Register bindings in the container.
    *
    * @return void
    */
    public function register()
    {
        // Merge published configuration with package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-route-restrictor.php', 'laravel-route-restrictor'
        );
    }
}
