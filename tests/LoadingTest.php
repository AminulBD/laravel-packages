<?php

namespace AminulBD\Package\Laravel\Tests;

use AminulBD\Package\Laravel\PackageActivationHandler;
use AminulBD\Package\Laravel\PackageManager;

class LoadingTest extends TestCase
{
    public function test_forced_roots_load_every_package_and_other_roots_only_enabled_ones(): void
    {
        $core = $this->makePackage('core', 'acme.core');
        $this->makePackage('addons', 'acme.billing');
        $this->makePackage('addons', 'acme.disabled');

        $this->bootWith([
            'roots' => ['core' => $this->root('core', true), 'addons' => $this->root('addons', false)],
            'enabled' => ['acme.billing'],
        ]);

        $manager = $this->app->make(PackageManager::class);
        $this->assertSame(['acme.billing', 'acme.core', 'acme.disabled'], $this->sorted(array_keys($manager->all())));
        $this->assertSame(['acme.core', 'acme.billing'], $this->registered());
        $this->assertSame('core', $manager->get('acme.core')['type']);
        $this->assertSame(['acme.billing', 'acme.disabled'], $this->sorted(array_keys($manager->filterBy('addons'))));
        $this->assertTrue(class_exists($core.'\\Marker'));
    }

    public function test_enabled_packages_can_come_from_a_callable_or_an_activation_handler(): void
    {
        $this->makePackage('addons', 'acme.one');
        $this->makePackage('addons', 'acme.two');

        $this->bootWith(['roots' => ['addons' => $this->root('addons', false)], 'enabled' => fn () => ['acme.two']]);
        $this->assertSame(['acme.two'], $this->registered());

        $this->bootWith(['roots' => ['addons' => $this->root('addons', false)], 'enabled' => OnlyOneActivator::class]);
        $this->assertSame(['acme.one'], $this->registered());
    }

    public function test_invalid_index_files_are_reported_as_unavailable(): void
    {
        $this->makePackage('core', 'acme.ok');
        @mkdir(base_path($this->fixtures.'/core/broken'), 0777, true);
        file_put_contents(base_path($this->fixtures.'/core/broken/index.php'), '<?php return "nope";');

        $this->bootWith(['roots' => ['core' => $this->root('core', true)]]);

        $unavailable = $this->app->make(PackageManager::class)->unavailable();
        $this->assertCount(1, $unavailable);
        $this->assertSame('Invalid package file.', $unavailable[0]['error']);
        $this->assertSame(['acme.ok'], $this->registered());
    }

    /** @param list<string> $ids */
    private function sorted(array $ids): array
    {
        sort($ids);

        return $ids;
    }
}

class OnlyOneActivator implements PackageActivationHandler
{
    public function enabled(): array
    {
        return ['acme.one'];
    }
}
