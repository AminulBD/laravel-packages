<?php

namespace AminulBD\Package\Laravel;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the package managers
        $this->app->singleton(PackageManager::class, fn () => new PackageManager());
        $manager = $this->app->make(PackageManager::class);

        // get all package roots
        $roots = config('packages.roots') ?? [];
        $paths = array_map(fn ($root) => base_path($root['location']).'/*/index.php', $roots);

        // register all packages from provided paths
        $manager->register($paths);

        // get all forced packages
        $forced = array_keys(array_filter($roots, fn ($path) => $path['forced'] ?? false));
        $packages = $manager->filterBy($forced);

        // load all forced packages
        $manager->load(array_keys($packages));

        foreach ($packages as $ext) {
            if (isset($ext['provider'])) {
                $this->app->register($ext['provider']);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../stubs/config/packages.php' => config_path('packages.php'),
        ], 'laravel-packages');

        $this->publishes([
            __DIR__.'/../stubs/packages/sample' => base_path('/packages/sample'),
        ], 'laravel-packages');

        $handler = config('packages.enabled');

        if (is_array($handler)) {
            $enabled = $handler;
        } elseif (is_callable($handler)) {
            $enabled = $handler();
        } elseif (is_string($handler) && class_exists($handler) && in_array(PackageActivationHandler::class, class_implements($handler))) {
            $enabled = (new $handler)->enabled();
        } else {
            $enabled = [];
        }

        if (empty($enabled)) {
            return;
        }

        $manager = $this->app->make(PackageManager::class);
        $roots = config('packages.roots');
        $nonForced = array_keys(array_filter($roots, fn ($root) => ! $root['forced']));
        $packages = $manager->filterBy($nonForced);

        $available = array_filter($packages, fn ($ext) => in_array($ext['id'], $enabled));
        $manager->load(array_keys($available));

        foreach ($available as $ext) {
            if (isset($ext['provider'])) {
                $this->app->register($ext['provider']);
            }
        }
    }
}
