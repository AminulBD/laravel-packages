<?php

namespace AminulBD\Package\Laravel;

/**
 * One process-wide PSR-4 autoloader for every loaded package namespace.
 *
 * Earlier versions called spl_autoload_register() once per namespace on every application boot and never removed
 * the closures. A process that boots the application many times (test suites, Octane, queue workers re-creating
 * the container) grew its autoloader stack on each boot, and every stale closure kept its PackageManager alive.
 * Now there is exactly one autoloader per process and the namespace map is de-duplicated, so booting again costs
 * nothing.
 */
final class PackageAutoloader
{
    /**
     * @var array<string, array<string, true>> namespace prefix (with trailing "\") => [directory => true]
     */
    private static array $prefixes = [];

    /**
     * Map a namespace prefix to a directory and make sure the autoloader is registered (once).
     */
    public static function add(string $namespace, string $path): void
    {
        $prefix = trim($namespace, '\\').'\\';
        $path = rtrim($path, '/\\');

        if (! isset(self::$prefixes[$prefix][$path])) {
            self::$prefixes[$prefix][$path] = true;
            // Longest prefix first, so "Acme\Blog\Seeders\" wins over "Acme\Blog\".
            uksort(self::$prefixes, function ($a, $b) {
                return strlen($b) <=> strlen($a) ?: strcmp($a, $b);
            });
        }

        if (! self::isRegistered()) {
            spl_autoload_register([self::class, 'load']);
        }
    }

    /**
     * The spl autoloader. Returns true when it loaded the class file.
     */
    public static function load(string $class): bool
    {
        foreach (self::$prefixes as $prefix => $paths) {
            if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
            foreach ($paths as $path => $_) {
                $file = $path.'/'.$relative;
                if (is_file($file)) {
                    require $file;

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, list<string>> namespace prefix => directories
     */
    public static function prefixes(): array
    {
        return array_map('array_keys', self::$prefixes);
    }

    public static function isRegistered(): bool
    {
        return in_array([self::class, 'load'], spl_autoload_functions(), true);
    }

    /**
     * Remove the autoloader and forget every mapping (mainly for tests).
     */
    public static function unregister(): void
    {
        spl_autoload_unregister([self::class, 'load']);
        self::$prefixes = [];
    }
}
