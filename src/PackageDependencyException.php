<?php

namespace AminulBD\Package\Laravel;

use RuntimeException;

/**
 * A package's `require` cannot be satisfied, or the `require` graph has a cycle.
 */
class PackageDependencyException extends RuntimeException
{
    /**
     * @param  list<string>  $missing
     */
    public static function missing(string $id, array $missing): self
    {
        return new self("Package [{$id}] requires missing or unavailable packages: ".implode(', ', $missing).'.');
    }

    /**
     * @param  list<string>  $ids
     */
    public static function cycle(array $ids): self
    {
        return new self('Package dependency cycle between: '.implode(', ', $ids).'.');
    }
}
