<?php

namespace AminulBD\Package\Laravel\Tests;

use AminulBD\Package\Laravel\PackageDependencyException;
use AminulBD\Package\Laravel\PackageManager;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;

class RequireTest extends TestCase
{
    /** @var list<\Throwable> */
    private array $reported = [];

    /**
     * Record what the package manager reports (it reports while the providers register, before any test code runs).
     */
    protected function resolveApplicationExceptionHandler($app)
    {
        $reported = &$this->reported;
        $reported = [];
        $app->singleton(ExceptionHandler::class, function ($app) use (&$reported) {
            return new class($app, $reported) extends Handler
            {
                private array $sink;

                public function __construct($container, array &$sink)
                {
                    parent::__construct($container);
                    $this->sink = &$sink;
                }

                public function report(\Throwable $e)
                {
                    $this->sink[] = $e;
                }
            };
        });
    }

    public function test_a_package_with_a_missing_requirement_is_skipped_recorded_and_reported(): void
    {
        $this->makePackage('core', 'acme.kernel');
        $this->makePackage('core', 'acme.broken', ['require' => ['acme.kernel', 'acme.nope']]);
        $this->makePackage('core', 'acme.dependent', ['require' => ['acme.broken']]);

        $this->bootWith(['roots' => ['core' => $this->root('core', true)]]);

        $manager = $this->app->make(PackageManager::class);
        $this->assertSame(['acme.kernel'], $this->registered());
        $this->assertSame(['acme.kernel'], $manager->loaded());

        $unavailable = collect($manager->unavailable())->keyBy('id');
        $this->assertSame(['acme.nope'], $unavailable['acme.broken']['missing']);
        $this->assertSame(['acme.broken'], $unavailable['acme.dependent']['missing']);
        $this->assertStringContainsString('requires missing or unavailable packages: acme.nope', $unavailable['acme.broken']['error']);
        $this->assertSame('core', $unavailable['acme.broken']['type']);
        $this->assertCount(2, $this->reported);
        $this->assertContainsOnlyInstancesOf(PackageDependencyException::class, $this->reported);
    }

    public function test_an_enabled_add_on_may_require_forced_packages_and_other_enabled_add_ons_only(): void
    {
        $this->makePackage('core', 'acme.kernel');
        $this->makePackage('addons', 'acme.stripe', ['require' => ['acme.kernel', 'acme.payments']]);
        $this->makePackage('addons', 'acme.payments', ['require' => ['acme.kernel']]);
        $this->makePackage('addons', 'acme.bkash', ['require' => ['acme.payments-local']]);
        $this->makePackage('addons', 'acme.payments-local');

        $this->bootWith([
            'roots' => ['core' => $this->root('core', true), 'addons' => $this->root('addons', false)],
            'enabled' => ['acme.stripe', 'acme.payments', 'acme.bkash'],
        ]);

        $this->assertSame(['acme.kernel', 'acme.payments', 'acme.stripe'], $this->registered());
        $this->assertSame(['acme.bkash'], array_column($this->app->make(PackageManager::class)->unavailable(), 'id'));
    }

    public function test_strict_mode_throws_instead_of_skipping(): void
    {
        $this->makePackage('core', 'acme.broken', ['require' => ['acme.nope']]);

        $this->expectException(PackageDependencyException::class);
        $this->expectExceptionMessage('Package [acme.broken] requires missing or unavailable packages: acme.nope.');

        $this->bootWith(['roots' => ['core' => $this->root('core', true)], 'strict' => true]);
    }

    public function test_resolve_can_be_used_without_the_service_provider(): void
    {
        $this->makePackage('core', 'acme.a', ['require' => ['acme.b']]);
        $this->makePackage('core', 'acme.b');

        $manager = new PackageManager;
        $manager->register(['core' => base_path($this->fixtures.'/core/*/index.php')]);

        $this->assertSame([], $manager->resolve(['acme.a']));
        $this->assertSame(['acme.a'], $manager->resolve(['acme.a'], ['acme.b']));
        $this->assertSame(['acme.b', 'acme.a'], $manager->resolve(['acme.a', 'acme.b']));
    }
}
