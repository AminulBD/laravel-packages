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
     * Ids of loaded packages, in load order.
     *
     * @var array<string, true>
     */
    private array $loaded = [];

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
     * Ids of the packages loaded so far, in load order (dependencies first).
     *
     * @return list<string>
     */
    public function loaded(): array
    {
        return array_keys($this->loaded);
    }

    public function isLoaded(string $id): bool
    {
        return isset($this->loaded[$id]);
    }

    /**
     * Ids a package declares in its `require` key.
     *
     * @return list<string>
     */
    public function requires(string $id): array
    {
        return $this->packages[$id]['require'] ?? [];
    }

    /**
     * @param array $paths
     *
     * @return void
     */
    public function register(array $paths): void
    {
        foreach ($paths as $type => $path) {
            foreach ($this->files($path) as $file) {
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

    /**
     * Load (autoload) the given packages in dependency order.
     *
     * @param array|string $packages
     *
     * @return void
     */
    public function load(array|string $packages): void
    {
        $packages = is_array($packages) ? $packages : [$packages];
        foreach ($this->sort($packages) as $id) {
            $ext = $this->packages[$id];
            if (isset($ext['autoload'])) {
                foreach ($ext['autoload'] as $namespace => $path) {
                    $path = rtrim($ext['path'], '/').'/'.$path;
                    $path = rtrim($path, '/');
                    $this->autoload($namespace, $path);
                }
            }

            $this->loaded[$id] = true;
        }
    }

    /**
     * Topological order of the given package ids: every package comes after the packages it requires.
     * Ties are broken by id, so the order is the same on every machine. Requirements outside the given set
     * are ignored here (see resolve()); unknown ids are dropped.
     *
     * @param list<string>|null $ids all registered packages when null
     *
     * @return list<string>
     *
     * @throws PackageDependencyException on a dependency cycle
     */
    public function sort(?array $ids = null): array
    {
        $ids = $ids === null ? array_keys($this->packages) : array_values(array_unique(array_map('strval', $ids)));
        $ids = array_values(array_filter($ids, fn ($id) => isset($this->packages[$id])));

        // Kahn's algorithm.
        $pending = [];
        $dependents = [];
        foreach ($ids as $id) {
            $requires = array_values(array_intersect(array_unique($this->requires($id)), $ids));
            $pending[$id] = count($requires);
            foreach ($requires as $required) {
                $dependents[$required][] = $id;
            }
        }

        $ready = array_keys(array_filter($pending, fn ($count) => $count === 0));
        sort($ready, SORT_STRING);
        $sorted = [];
        while ($ready !== []) {
            $id = array_shift($ready);
            $sorted[] = $id;
            foreach ($dependents[$id] ?? [] as $dependent) {
                if (--$pending[$dependent] === 0) {
                    $ready[] = $dependent;
                    sort($ready, SORT_STRING);
                }
            }
        }

        if (count($sorted) !== count($ids)) {
            $cycle = array_values(array_diff($ids, $sorted));
            sort($cycle, SORT_STRING);

            throw PackageDependencyException::cycle($cycle);
        }

        return $sorted;
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
     * Package index files matching a glob pattern, in byte order so that every filesystem
     * (APFS, ext4, NTFS...) yields the same order.
     *
     * @return list<string>
     */
    private function files(string $pattern): array
    {
        $files = glob($pattern, GLOB_NOSORT) ?: [];
        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * @param string $namespace
     * @param string $path
     *
     * @return void
     */
    private function autoload(string $namespace, string $path): void
    {
        // One shared autoloader per process instead of a new closure per namespace on every boot.
        PackageAutoloader::add($namespace, $path);
    }

    /**
     * Map packages with default values.
     *
     * @param array $package
     *
     * @return array
     */
    private function mapWithDefaults(array $package): array
    {
        $package = array_merge([
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

        $package['require'] = array_values(array_map('strval', (array) $package['require']));

        return $package;
    }
}
