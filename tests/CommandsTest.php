<?php

namespace AminulBD\Package\Laravel\Tests;

use AminulBD\Package\Laravel\PackageManager;
use Illuminate\Support\ServiceProvider;

class CommandsTest extends TestCase
{
    private function config(array $extra = []): array
    {
        return array_merge([
            'roots' => ['core' => $this->root('core', true), 'addons' => $this->root('addons')],
            'enabled' => ['acme.billing'],
            'cache' => base_path($this->fixtures.'/cache/laravel-packages.php'),
        ], $extra);
    }

    public function test_packages_cache_writes_a_manifest_that_later_boots_use_instead_of_scanning(): void
    {
        $this->makePackage('core', 'acme.kernel');
        $this->makePackage('addons', 'acme.billing', ['require' => ['acme.kernel']]);
        $this->bootWith($this->config());

        $this->artisan('packages:cache')->assertSuccessful();
        $manifest = base_path($this->fixtures.'/cache/laravel-packages.php');
        $this->assertFileExists($manifest);

        // A package added after caching is invisible until the cache is cleared or rebuilt.
        $this->makePackage('core', 'acme.late');
        $this->bootWith($this->config());
        $this->assertSame(['acme.kernel', 'acme.billing'], $this->registered());
        $this->assertNull($this->app->make(PackageManager::class)->get('acme.late'));

        $this->artisan('packages:clear')->assertSuccessful();
        $this->assertFileDoesNotExist($manifest);
        $this->bootWith($this->config());
        $this->assertSame(['acme.kernel', 'acme.late', 'acme.billing'], $this->registered());
    }

    public function test_a_manifest_built_for_other_roots_is_ignored(): void
    {
        $this->makePackage('core', 'acme.kernel');
        $this->bootWith($this->config());
        $this->artisan('packages:cache')->assertSuccessful();

        $this->makePackage('other', 'acme.other');
        $this->bootWith($this->config(['roots' => ['other' => $this->root('other', true)]]));

        $this->assertSame(['acme.other'], $this->registered());
    }

    public function test_packages_cache_refuses_index_files_with_closures(): void
    {
        $this->makePackage('core', 'acme.kernel');
        file_put_contents(base_path($this->fixtures.'/core/acme.kernel/index.php'), "<?php return ['id' => 'acme.kernel', 'config' => ['x' => fn () => 1]];");
        $this->bootWith($this->config());

        $this->artisan('packages:cache')->assertFailed()->expectsOutputToContain('acme.kernel');
        $this->assertFileDoesNotExist(base_path($this->fixtures.'/cache/laravel-packages.php'));
    }

    public function test_packages_list_shows_root_enabled_loaded_and_why_a_package_is_unavailable(): void
    {
        $this->makePackage('core', 'acme.kernel', ['version' => '1.2.0']);
        $this->makePackage('addons', 'acme.billing', ['require' => ['acme.kernel']]);
        $this->makePackage('addons', 'acme.off');
        $this->makePackage('addons', 'acme.broken', ['require' => ['acme.nope']]);
        $this->bootWith($this->config(['enabled' => ['acme.billing', 'acme.broken']]));

        $this->artisan('packages:list')
            ->assertSuccessful()
            ->expectsTable(['ID', 'Root', 'Version', 'Enabled', 'Loaded', 'Unavailable'], [
                ['acme.billing', 'addons', '', 'yes', 'yes', ''],
                ['acme.broken', 'addons', '', 'yes', 'no', 'Package [acme.broken] requires missing or unavailable packages: acme.nope.'],
                ['acme.off', 'addons', '', 'no', 'no', ''],
                ['acme.kernel', 'core', '1.2.0', 'forced', 'yes', ''],
            ]);

        $this->artisan('packages:list --json')->assertSuccessful()->expectsOutputToContain('"id": "acme.kernel"');
    }

    public function test_optimize_and_optimize_clear_include_the_package_cache(): void
    {
        if (! property_exists(ServiceProvider::class, 'optimizeCommands')) {
            $this->markTestSkipped('ServiceProvider::optimizes() needs Laravel 11.27+.');
        }

        $this->assertContains('packages:cache', ServiceProvider::$optimizeCommands);
        $this->assertContains('packages:clear', ServiceProvider::$optimizeClearCommands);
    }
}
