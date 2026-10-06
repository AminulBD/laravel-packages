<?php

namespace AminulBD\Package\Laravel\Tests;

use AminulBD\Package\Laravel\PackageAutoloader;
use AminulBD\Package\Laravel\PackageManager;

class AutoloaderTest extends TestCase
{
    public function test_booting_the_application_repeatedly_does_not_grow_the_autoloader_stack(): void
    {
        $this->makePackage('core', 'acme.one');
        $this->makePackage('core', 'acme.two');
        $this->makePackage('core', 'acme.three');
        $config = ['roots' => ['core' => $this->root('core', true)]];

        $this->bootWith($config);
        $before = count(spl_autoload_functions());
        $prefixes = PackageAutoloader::prefixes();

        for ($i = 0; $i < 5; $i++) {
            $this->bootWith($config);
        }

        $this->assertSame($before, count(spl_autoload_functions()));
        $this->assertSame($prefixes, PackageAutoloader::prefixes());
        $this->assertCount(3, $this->registered());
    }

    public function test_no_closure_keeps_an_old_package_manager_alive(): void
    {
        $this->makePackage('core', 'acme.one');
        $this->bootWith(['roots' => ['core' => $this->root('core', true)]]);
        $this->bootWith(['roots' => ['core' => $this->root('core', true)]]);

        foreach (spl_autoload_functions() as $loader) {
            if ($loader instanceof \Closure) {
                $this->assertNotInstanceOf(PackageManager::class, (new \ReflectionFunction($loader))->getClosureThis());
            }
        }
    }

    public function test_the_longest_matching_prefix_wins_and_unknown_classes_fall_through(): void
    {
        $base = base_path($this->fixtures.'/manual');
        @mkdir($base.'/src', 0777, true);
        @mkdir($base.'/seeders', 0777, true);
        file_put_contents($base.'/src/Thing.php', "<?php namespace {$this->ns}\\Blog; class Thing {}");
        file_put_contents($base.'/seeders/BlogSeeder.php', "<?php namespace {$this->ns}\\Blog\\Seeders; class BlogSeeder {}");

        PackageAutoloader::add($this->ns.'\\Blog\\', $base.'/src/');
        PackageAutoloader::add($this->ns.'\\Blog\\Seeders', $base.'/seeders');

        $this->assertTrue(class_exists($this->ns.'\\Blog\\Thing'));
        $this->assertTrue(class_exists($this->ns.'\\Blog\\Seeders\\BlogSeeder'));
        $this->assertFalse(PackageAutoloader::load($this->ns.'\\Blog\\Missing'));
        $this->assertFalse(PackageAutoloader::load($this->ns.'\\BlogOther\\Thing'));
    }
}
