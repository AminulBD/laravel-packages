<?php

namespace AminulBD\Package\Laravel;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the package manager
        $this->app->singleton(PackageManager::class, fn () => new PackageManager());

        // Retrieve package manager instance
        $manager = $this->app->make(PackageManager::class);

        // Get all package roots and register all packages
        $this->registerPackages($manager);

        // Load forced packages
        $this->loadForcedPackages($manager);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish data only if running in console
        if (App::runningInConsole()) {
            $this->publishData();
        }

        // Get enabled packages
        $enabled = $this->getEnabledPackages();

        // Return early if no enabled packages
        if (empty($enabled)) {
            return;
        }

        // Retrieve the package manager instance
        $manager = $this->app->make(PackageManager::class);

        // Get package roots and filter non-forced packages
        $roots = config('packages.roots');
        $nonForced = $this->filterNonForcedPackages($roots);
        $packages = $manager->filterBy($nonForced);

        // Load and register enabled packages
        $this->loadEnabledPackages($enabled, $packages, $manager);
    }

    /**
     * Register all packages from the provided paths.
     */
    private function registerPackages(PackageManager $manager): void
    {
        $roots = config('packages.roots') ?? [];
        $paths = array_map(fn ($root) => base_path($root['location']) . '/*/index.php', $roots);

        $manager->register($paths);
    }

    /**
     * Load forced packages.
     */
    private function loadForcedPackages(PackageManager $manager): void
    {
        $roots = config('packages.roots') ?? [];
        $forced = array_keys(array_filter($roots, fn ($path) => $path['forced'] ?? false));
        $packages = $manager->filterBy($forced);

        $manager->load(array_keys($packages));
    }

    /**
     * Get the enabled packages configuration.
     */
    private function getEnabledPackages(): array
    {
        $handler = config('packages.enabled');
        $enabled = [];

        // Determine enabled packages based on the handler
        if (is_array($handler)) {
            $enabled = $handler;
        }

        if (is_callable($handler)) {
            $enabled = $handler();
        }

        if (is_string($handler) && class_exists($handler) && in_array(PackageActivationHandler::class, class_implements($handler))) {
            $enabled = (new $handler)->enabled();
        }

        return $enabled;
    }

    /**
     * Filter and return non-forced packages.
     */
    private function filterNonForcedPackages(array $roots): array
    {
        return array_keys(array_filter($roots, fn ($root) => ! $root['forced']));
    }

    /**
     * Load enabled packages and register their providers and schedules.
     */
    private function loadEnabledPackages(array $enabled, array $packages, PackageManager $manager): void
    {
        $enabledPackages = array_filter($packages, fn ($pkg) => in_array($pkg['id'], $enabled));

        // Load enabled packages
        $manager->load(array_keys($enabledPackages));

        // Register providers and schedules for each enabled package
        foreach ($enabledPackages as $enabledPackage) {
            $this->registerPackageProviders($enabledPackage);
            $this->registerPackageSchedules($enabledPackage);
        }
    }

    /**
     * Register package providers.
     */
    private function registerPackageProviders(array $enabledPackage): void
    {
        if (isset($enabledPackage['providers'])) {
            $providers = (array) $enabledPackage['providers']; // Ensure providers is always an array

            foreach ($providers as $provider) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * Register package schedules.
     */
    private function registerPackageSchedules(array $enabledPackage): void
    {
        if (App::runningInConsole() && isset($enabledPackage['schedules'])) {
            $schedulePaths = (array) $enabledPackage['schedules']; // Ensure schedules is always an array

            // Register schedules for enabled package
            $this->app->booted(function () use ($schedulePaths, $enabledPackage) {
                $schedule = $this->app->make(Schedule::class);

                foreach ($schedulePaths as $schedulePath) {
                    include $enabledPackage['path'] . DIRECTORY_SEPARATOR . $schedulePath;
                }
            });
        }
    }

    /**
     * Publish necessary package assets and config files.
     */
    private function publishData(): void
    {
        $this->publishes([
            __DIR__ . '/../stubs/config/packages.php' => config_path('packages.php'),
        ], 'laravel-packages');

        $this->publishes([
            __DIR__ . '/../stubs/packages/sample' => base_path('/packages/sample'),
        ], 'laravel-packages');
    }
}
