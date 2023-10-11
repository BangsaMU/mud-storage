<?php

namespace Bangsamu\Storage;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

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
        //buat scredule
        $this->app->booted(function () {
            // $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            //     $schedule->call(function () {
            //         //Pengecekan apakah cronjob berhasil atau tidak
            //         //Mencatat info log
            //         Log::info('user: sys url: ' . url()->current() . ' message:BEAT STORAGE ' . app()->environment());
            //     })->everyMinute();
            // });
            $schedule = $this->app->make(Schedule::class);
            // $schedule->command('some:command')->everyMinute();
            if (app()->environment() != 'production') {
                $schedule->call(function () {
                    //Pengecekan apakah cronjob berhasil atau tidak
                    //Mencatat info log
                    // Log::info('user: sys url: ' . url()->current() . ' message:BEAT STORAGE ' . app()->environment());
                })->name('test-schedule')->withoutOverlapping()
                    ->everyMinute();
            }

            /*akan melakukan backup tengah malam setiap hari log activity dari H -1 */
            // $schedule->call('Bangsamu\Storage\Controllers\StorageController@log', ['folder' => 'parameter3', 'backup' => true])
            //     ->name('cron-storage-log')
            //     ->withoutOverlapping()
            //     ->everyMinute()
            //     ->appendOutputTo(storage_path('logs/schedule.log'));

            $schedule->call('Bangsamu\Storage\Controllers\StorageController@scanDir', ['folder' => config('StorageConfig.main.FOLDER'), 'scan' => false, 'backup' => config('StorageConfig.main.ACTIVE')])
                ->name('cron-storage-scan')
                ->withoutOverlapping()
                ->everyMinute()
                ->appendOutputTo(storage_path('logs/schedule.log'));

            // $schedule->call('Bangsamu\Storage\Controllers\StorageController@getListLokal', ['folder' => config('StorageConfig.main.FOLDER'), 'backup' => config('StorageConfig.main.ACTIVE')])
            //     ->name('cron-storage-all')
            //     ->withoutOverlapping()
            //     ->everyMinute()
            //     ->appendOutputTo(storage_path('logs/schedule.log'));
        });
        //
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
        $this->publishes([
            __DIR__ . '/../resources/config/StorageConfig.php' => config_path('StorageConfig.php'),
        ]);
        // $this->loadViewsFrom(__DIR__ . '/../resources/views', 'storage');

        $this->publishes([
            __DIR__ . '/../resources/db/' => database_path('./'),
        ]);
        // $this->publishes([
        //     __DIR__ . '/../resources/views' => resource_path('views/vendor/storage'),
        // ]);

        // $this->publishes([
        //     __DIR__.'/../resources/views/' => resource_path('views/adminlte/auth/login.blade.php'),
        // ]);

        // $this->publishes([
        //     __DIR__.'/routes.php' => base_path('routes/storage.php'),
        // ]);
    }
}
