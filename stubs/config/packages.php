<?php

return [
    'roots' => [
        'default' => [
            'forced' => false, // true = load every package in this root; false (or omitted) = only the enabled ones
            'location' => '/packages',
        ],
    ],

    // this can be array of package ids, callable function that returns array of package ids, or class that implements \AminulBD\Package\Laravel\PackageActivationHandler
    'enabled' => [
        'yourdomain.sample',
    ],

    // true = throw a PackageDependencyException when a package's `require` is not met;
    // false = skip that package, list it in PackageManager::unavailable() and report() the exception.
    'strict' => false,

    // where `php artisan packages:cache` writes the package manifest (null = bootstrap/cache/laravel-packages.php)
    'cache' => null,
];
