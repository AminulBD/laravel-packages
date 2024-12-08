<?php

namespace AminulBD\Package\Laravel;

class PackageManager
{
    /**
     * @var array
     */
    private array $packages = [];

    /**
     * @var array
     */
    private array $unavailable = [];

    /**
     * @param string $id
     *
     * @return mixed|null
     */
    public function get(string $id): mixed
    {
        return $this->packages[$id] ?? null;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->packages;
    }

    /**
     * @return array
     */
    public function unavailable(): array
    {
        return $this->unavailable;
    }

    /**
     * @param array $paths
     *
     * @return void
     */
    public function register(array $paths): void
    {
        foreach ($paths as $type => $path) {
            $files = glob($path, GLOB_NOSORT);

            foreach ($files as $file) {
                try {
                    if (! is_array($ext = include $file) || ! isset($ext['id'])) {
                        $this->unavailable[] = [
                            'type' => $type,
                            'file' => $file,
                            'error' => 'Invalid package file.',
                        ];

                        continue;
                    }

                    $ext['path'] = dirname($file);
                    $ext['type'] = $type;

                    $this->packages[$ext['id']] = $this->mapWithDefaults($ext);
                } catch (\Throwable $e) {
                    $this->unavailable[] = [
                        'type' => $type,
                        'file' => $file,
                        'error' => $e->getMessage(),
                    ];

                    continue;
                }
            }
        }
    }

    public function load(array|string $packages): void
    {
        $packages = is_array($packages) ? $packages : [$packages];
        foreach ($packages as $ext) {
            if (! isset($this->packages[$ext])) {
                continue;
            }

            $ext = $this->packages[$ext];
            if (isset($ext['autoload'])) {
                foreach ($ext['autoload'] as $namespace => $path) {
                    $path = rtrim($ext['path'], '/').'/'.$path;
                    $path = rtrim($path, '/');
                    $this->autoload($namespace, $path);
                }
            }
        }
    }

    /**
     * @param array|string $keys
     *
     * @return array
     */
    public function filterBy(array|string $keys): array
    {
        $keys = is_array($keys) ? $keys : [$keys];

        return array_filter($this->packages, fn ($ext) => in_array($ext['type'], $keys));
    }

    /**
     * @param string $namespace
     * @param string $path
     *
     * @return void
     */
    private function autoload(string $namespace, string $path): void
    {
        spl_autoload_register(function ($class) use ($namespace, $path) {
            $len = strlen($namespace);
            if (strncmp($namespace, $class, $len) !== 0) {
                return;
            }

            $class = substr($class, $len);
            $file = $path.DIRECTORY_SEPARATOR.str_replace('\\', '/', $class).'.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }

    /**
     * Map extension with default values.
     *
     * @param array $package
     *
     * @return array
     */
    private function mapWithDefaults(array $package): array
    {
        return array_merge([
            'id' => null,
            'path' => null,
            'type' => null,
            'name' => null,
            'description' => null,
            'version' => null,
            'icon' => null,
            'developer' => null,
            'developer_url' => null,
            'support_url' => null,
            'support_email' => null,
            'docs_url' => null,
            'is_active' => null,
            'provider' => [],
            'require' => [],
            'files' => [],
            'autoload' => [],
            'config' => [],
        ], $package);
    }
}
