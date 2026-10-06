<?php

namespace AminulBD\Package\Laravel;

use AminulBD\Package\Laravel\Console\CacheCommand;
use AminulBD\Package\Laravel\Console\ClearCommand;
use AminulBD\Package\Laravel\Console\ListCommand;
use Illuminate\Contracts\Foundation\Application;
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
        $this->app->singleton(PackageManifest::class, fn ($app) => new PackageManifest(static::manifestPath($app)));
        $manager = $this->app->make(PackageManager::class);

        // get all package roots
        $roots = static::roots();
        $paths = static::paths($roots);

        // register all packages from provided paths (or from the cached manifest, see packages:cache)
        $cached = $this->app->make(PackageManifest::class)->read($paths);
        if ($cached !== null) {
            $manager->registerManifest($cached);
        } else {
            $manager->register($paths);
        }

        // get all forced packages
        $forced = array_keys(array_filter($roots, fn ($root) => $root['forced']));
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

        if ($this->app->runningInConsole()) {
            $this->commands([CacheCommand::class, ClearCommand::class, ListCommand::class]);
        }

        // `php artisan optimize` / `optimize:clear` also cache / clear the package manifest (Laravel 11.27+).
        if (method_exists($this, 'optimizes')) {
            $this->optimizes(optimize: 'packages:cache', clear: 'packages:clear', key: 'laravel-packages');
        }

        $enabled = static::enabled($this->app);

        if (empty($enabled)) {
            return;
        }

        $manager = $this->app->make(PackageManager::class);
        $roots = static::roots();
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

    /**
     * config('packages.roots') with defaults: a root without a `forced` key is not forced,
     * and a root without a `location` is ignored.
     *
     * @return array<string, array{forced: bool, location: string}>
     */
    public static function roots(): array
    {
        $roots = [];
        foreach ((array) (config('packages.roots') ?? []) as $name => $root) {
            if (! is_array($root) || ! isset($root['location'])) {
                continue;
            }

            $roots[$name] = ['forced' => (bool) ($root['forced'] ?? false), 'location' => (string) $root['location']];
        }

        return $roots;
    }

    /**
     * Glob pattern of the index files of each root.
     *
     * @param  array<string, array{location: string}>  $roots
     * @return array<string, string>
     */
    public static function paths(array $roots): array
    {
        return array_map(fn ($root) => base_path($root['location']).'/*/index.php', $roots);
    }

    /**
     * Ids returned by config('packages.enabled'): an array, a callable, or a PackageActivationHandler class
     * (resolved from the container).
     *
     * @return list<string>
     */
    public static function enabled(Application $app): array
    {
        $handler = config('packages.enabled');

        if (is_array($handler)) {
            $enabled = $handler;
        } elseif (is_callable($handler)) {
            $enabled = $handler();
        } elseif (is_string($handler) && class_exists($handler) && in_array(PackageActivationHandler::class, class_implements($handler))) {
            $enabled = $app->make($handler)->enabled();
        } else {
            $enabled = [];
        }

        return array_values(array_map('strval', (array) $enabled));
    }

    /**
     * Where packages:cache writes the manifest: config('packages.cache'), default bootstrap/cache/laravel-packages.php.
     */
    public static function manifestPath(Application $app): string
    {
        return (string) (config('packages.cache') ?: $app->bootstrapPath('cache/laravel-packages.php'));
    }
}
