<?php

namespace AminulBD\Package\Laravel\Tests;

class ConfigTest extends TestCase
{
    public function test_a_root_without_a_forced_key_is_treated_as_not_forced(): void
    {
        $this->makePackage('addons', 'acme.one');
        $this->makePackage('addons', 'acme.two');

        // Used to raise "Undefined array key "forced"" in boot() (an ErrorException under Laravel's handler).
        $this->bootWith(['roots' => ['addons' => $this->root('addons')], 'enabled' => ['acme.two']]);

        $this->assertSame(['acme.two'], $this->registered());
    }

    public function test_enabled_packages_without_any_roots_do_nothing(): void
    {
        $this->bootWith(['enabled' => ['acme.one']]);

        $this->assertSame([], $this->registered());
    }
}
