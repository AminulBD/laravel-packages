<?php

namespace YourDomain\Sample;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use YourDomain\Sample\Commands\SampleCommand;
use YourDomain\Sample\Controllers\SampleController;

class SampleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // you can register custom config file with override capabilities
        $this->mergeConfigFrom(
            __DIR__.'/../config/sample.php', 'sample'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // this will allow you to run: `php artisan migrate`
            // All migrations files from the `../migrations` will be used.
            $this->loadMigrationsFrom(__DIR__.'/../migrations');

            // Even you can register commands
            $this->commands([
                SampleCommand::Class,
            ]);
        }

        // this will allow you to use views by using namespaces. for example: sample::VIEW_NAME
        View::addNamespace('sample', __DIR__.'/../views');

        // Even you can define routes.
        Route::get('/sample', [SampleController::class, 'sample'])->name('sample');

        // Alternatively you can do this way:
        // $this->loadRoutesFrom(__DIR__.'/../routes/sample.php');
    }
}
