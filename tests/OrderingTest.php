<?php

namespace AminulBD\Package\Laravel\Tests;

use AminulBD\Package\Laravel\PackageDependencyException;
use AminulBD\Package\Laravel\PackageManager;

class OrderingTest extends TestCase
{
    public function test_discovery_order_is_sorted_by_path_on_every_filesystem(): void
    {
        foreach (['zeta', 'alpha', 'mike', 'bravo', 'yankee', 'charlie'] as $name) {
            $this->makePackage('core', 'acme.'.$name);
        }

        $this->bootWith(['roots' => ['core' => $this->root('core', true)]]);

        $expected = ['acme.alpha', 'acme.bravo', 'acme.charlie', 'acme.mike', 'acme.yankee', 'acme.zeta'];
        $this->assertSame($expected, array_keys($this->app->make(PackageManager::class)->all()));
        $this->assertSame($expected, $this->registered());
    }

    public function test_packages_load_after_the_packages_they_require(): void
    {
        $this->makePackage('core', 'acme.app', ['require' => ['acme.billing', 'acme.users']]);
        $this->makePackage('core', 'acme.billing', ['require' => ['acme.users']]);
        $this->makePackage('core', 'acme.users', ['require' => ['acme.kernel']]);
        $this->makePackage('core', 'acme.kernel');
        $this->makePackage('core', 'acme.audit', ['require' => 'acme.kernel']);

        $this->bootWith(['roots' => ['core' => $this->root('core', true)]]);

        $expected = ['acme.kernel', 'acme.audit', 'acme.users', 'acme.billing', 'acme.app'];
        $this->assertSame($expected, $this->registered());
        $this->assertSame($expected, $this->app->make(PackageManager::class)->loaded());
        $this->assertTrue($this->app->make(PackageManager::class)->isLoaded('acme.app'));
    }

    public function test_enabled_packages_from_other_roots_load_in_dependency_order_after_forced_ones(): void
    {
        $this->makePackage('core', 'acme.kernel');
        $this->makePackage('addons', 'acme.a-payments', ['require' => ['acme.kernel', 'acme.z-ledger']]);
        $this->makePackage('addons', 'acme.z-ledger', ['require' => ['acme.kernel']]);

        $this->bootWith([
            'roots' => ['core' => $this->root('core', true), 'addons' => $this->root('addons', false)],
            'enabled' => ['acme.a-payments', 'acme.z-ledger'],
        ]);

        $this->assertSame(['acme.kernel', 'acme.z-ledger', 'acme.a-payments'], $this->registered());
    }

    public function test_sort_is_deterministic_and_detects_cycles(): void
    {
        $this->makePackage('core', 'acme.a', ['require' => ['acme.b']]);
        $this->makePackage('core', 'acme.b', ['require' => ['acme.c']]);
        $this->makePackage('core', 'acme.c', ['require' => ['acme.a']]);
        $this->makePackage('core', 'acme.d');

        $manager = new PackageManager;
        $manager->register(['core' => base_path($this->fixtures.'/core/*/index.php')]);

        $this->assertSame(['acme.d'], $manager->sort(['acme.d', 'acme.unknown']));
        $this->assertSame(['acme.b', 'acme.a'], $manager->sort(['acme.a', 'acme.b']));

        $this->expectException(PackageDependencyException::class);
        $this->expectExceptionMessage('acme.a, acme.b, acme.c');
        $manager->sort();
    }
}
