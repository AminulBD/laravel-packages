<?php

namespace AminulBD\Package\Laravel\Tests;

use AminulBD\Package\Laravel\PackageServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Fixture packages are written below this directory (relative to the base path), one sub-directory per root.
     */
    protected string $fixtures;

    /**
     * Unique namespace prefix of this test's fixture classes (classes stay defined for the whole PHP process).
     */
    protected string $ns;

    /**
     * @var array<string, mixed> config('packages') used when the application is (re)created
     */
    protected array $packagesConfig = [];

    protected function setUp(): void
    {
        $id = str_replace('.', '', uniqid('', true));
        $this->fixtures = 'tests-fixtures-'.$id;
        $this->ns = 'Fixture'.$id;
        $GLOBALS['laravel_packages_registered'] = [];

        parent::setUp();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory(base_path($this->fixtures));

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [PackageServiceProvider::class];
    }

    /**
     * Package providers register before defineEnvironment() runs, so config('packages') is set right after the
     * configuration is loaded.
     */
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('packages', $this->packagesConfig);
    }

    /**
     * Recreate the application with the given config('packages').
     *
     * @param  array<string, mixed>  $config
     */
    protected function bootWith(array $config): void
    {
        $this->packagesConfig = $config;
        $GLOBALS['laravel_packages_registered'] = [];
        $this->refreshApplication();
    }

    /**
     * A root definition pointing at the fixture directory of $root.
     *
     * @return array{forced?: bool, location: string}
     */
    protected function root(string $root, ?bool $forced = null): array
    {
        $definition = ['location' => '/'.$this->fixtures.'/'.$root];
        if ($forced !== null) {
            $definition['forced'] = $forced;
        }

        return $definition;
    }

    /**
     * Write a fixture package with a service provider that records its registration order.
     *
     * @param  array<string, mixed>  $index  extra index.php keys (e.g. require)
     */
    protected function makePackage(string $root, string $id, array $index = []): string
    {
        $dir = base_path($this->fixtures.'/'.$root.'/'.$id);
        $class = str_replace(['.', '-'], '', ucwords($id, '.-'));
        $namespace = $this->ns.'\\'.$class;
        @mkdir($dir.'/src', 0777, true);

        file_put_contents($dir.'/src/'.$class.'ServiceProvider.php', <<<PHP
<?php

namespace {$namespace};

class {$class}ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        \$GLOBALS['laravel_packages_registered'][] = '{$id}';
    }
}
PHP);
        file_put_contents($dir.'/src/Marker.php', "<?php\n\nnamespace {$namespace};\n\nclass Marker {}\n");

        $index = array_merge([
            'id' => $id,
            'autoload' => [$namespace.'\\' => 'src/'],
            'provider' => $namespace.'\\'.$class.'ServiceProvider',
        ], $index);
        file_put_contents($dir.'/index.php', '<?php return '.var_export($index, true).';');

        return $namespace;
    }

    /** @return list<string> ids of fixture providers in registration order */
    protected function registered(): array
    {
        return $GLOBALS['laravel_packages_registered'];
    }
}
