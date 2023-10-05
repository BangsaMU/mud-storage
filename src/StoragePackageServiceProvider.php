<?php

namespace Bangsamu\Storage;

use Illuminate\Support\ServiceProvider;

class StoragePackageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->publishes([
            __DIR__.'/../resources/config/StorageConfig.php' => config_path('StorageConfig.php'),
        ]);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'storage');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/storage'),
        ]);

        // $this->publishes([
        //     __DIR__.'/../resources/views/' => resource_path('views/adminlte/auth/login.blade.php'),
        // ]);

        // $this->publishes([
        //     __DIR__.'/routes.php' => base_path('routes/storage.php'),
        // ]);
    }
}
