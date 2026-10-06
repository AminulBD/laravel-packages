<?php

namespace AminulBD\Package\Laravel;

use LogicException;

/**
 * The cached package scan (`php artisan packages:cache`), like Laravel's config cache.
 *
 * Without it every boot globs each root and includes every index.php. With it, register() reads one PHP file
 * (opcache-friendly). The manifest records the glob patterns it was built from and is ignored when they no
 * longer match the configured roots (e.g. the roots changed, or the app moved to another directory).
 */
class PackageManifest
{
    public const VERSION = 1;

    /**
     * @var string
     */
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    /**
     * The cached discovery result, or null when there is no usable manifest for these glob patterns.
     *
     * @param array<string, string> $paths
     *
     * @return array{packages: array<string, array>, unavailable: list<array>}|null
     */
    public function read(array $paths): ?array
    {
        if (! $this->exists()) {
            return null;
        }

        $manifest = require $this->path;

        if (! is_array($manifest) || ($manifest['version'] ?? null) !== self::VERSION || ($manifest['paths'] ?? null) !== $paths) {
            return null;
        }

        return [
            'packages' => $manifest['packages'] ?? [],
            'unavailable' => $manifest['unavailable'] ?? [],
        ];
    }

    /**
     * @param array<string, string>                                                $paths
     * @param array{packages: array<string, array>, unavailable: list<array>} $discovered
     *
     * @throws LogicException when an index.php returns a value that cannot be written to PHP (closure, object)
     */
    public function write(array $paths, array $discovered): void
    {
        foreach ($discovered['packages'] as $id => $package) {
            $this->assertExportable($package, (string) $id);
        }

        $manifest = [
            'version' => self::VERSION,
            'paths' => $paths,
            'packages' => $discovered['packages'],
            'unavailable' => $discovered['unavailable'],
        ];

        $directory = dirname($this->path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write to a temporary file and rename it, so a concurrent boot never reads a half-written manifest.
        $temporary = $this->path.'.'.bin2hex(random_bytes(4)).'.tmp';
        file_put_contents($temporary, '<?php return '.var_export($manifest, true).';'.PHP_EOL);
        rename($temporary, $this->path);

        $this->invalidateOpcache();
    }

    public function delete(): bool
    {
        if (! $this->exists()) {
            return false;
        }

        $this->invalidateOpcache();

        return unlink($this->path);
    }

    /**
     * @param mixed $value
     */
    private function assertExportable($value, string $id, string $key = ''): void
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->assertExportable($v, $id, ltrim($key.'.'.$k, '.'));
            }

            return;
        }

        if (is_object($value) || is_resource($value)) {
            throw new LogicException("Package [{$id}] cannot be cached: [{$key}] in its index.php is not a plain value (closure or object).");
        }
    }

    private function invalidateOpcache(): void
    {
        if (function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
            @opcache_invalidate($this->path, true);
        }
    }
}
