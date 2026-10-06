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

        // load all forced packages, dependencies first
        $this->loadPackages($manager, array_keys($packages));
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
        $this->loadPackages($manager, array_keys($available));
    }

    /**
     * Autoload the packages and register their service providers in dependency order. Packages whose `require`d
     * packages are neither loaded nor part of this batch are skipped and listed in unavailable(), or throw when
     * config('packages.strict') is true.
     *
     * @param  list<string>  $ids
     */
    protected function loadPackages(PackageManager $manager, array $ids): void
    {
        $ids = $manager->resolve($ids, $manager->loaded(), (bool) config('packages.strict', false));
        $manager->load($ids);

        foreach ($ids as $id) {
            foreach ((array) ($manager->get($id)['provider'] ?? []) as $provider) {
                if ($provider) {
                    $this->app->register($provider);
                }
            }
        }
    }
}
