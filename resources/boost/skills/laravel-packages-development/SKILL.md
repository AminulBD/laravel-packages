---
name: laravel-packages-development
description: "Builds and debugs modules loaded by aminulbd/laravel-packages (directories with an index.php under config('packages.roots')). Activates when creating a package/module, editing an index.php (id, require, autoload, provider), enabling or disabling packages, writing a PackageActivationHandler, fixing load order or missing-requirement errors (PackageDependencyException, PackageManager::unavailable()), or running packages:cache, packages:clear or packages:list."
license: MIT
metadata:
  author: AminulBD
---
# Laravel Packages (aminulbd/laravel-packages)

Modules live in directories under the roots of `config/packages.php`. Each module is a directory with an
`index.php` that returns an array. The package service provider discovers them, autoloads them and registers their
service providers.

## Configuration (`config/packages.php`)

```php
return [
    'roots' => [
        'core'   => ['forced' => true,  'location' => '/packages/core'],   // every package here always loads
        'addons' => ['forced' => false, 'location' => '/packages/addons'], // only the ids from `enabled` load
    ],
    'enabled' => App\Packages\Activator::class, // array of ids | callable | PackageActivationHandler class
    'strict' => in_array(env('APP_ENV'), ['local', 'testing']), // unmet `require` throws instead of skipping
    'cache' => null, // packages:cache target, default bootstrap/cache/laravel-packages.php
];
```

- `location` is relative to the project base path. A root without `forced` is not forced.
- An activation handler implements `AminulBD\Package\Laravel\PackageActivationHandler::enabled(): array` and is
  resolved from the container. It must never throw during boot (e.g. fall back when its table doesn't exist yet).

## Creating a package

1. Create `<root location>/<dir>/index.php`:
   ```php
   <?php
   return [
       'id' => 'acme.billing',                       // unique, used by `enabled` and `require`
       'name' => 'Billing',
       'version' => '1.0.0',
       'require' => ['acme.kernel', 'acme.users'],   // ids this package needs
       'autoload' => ['Acme\\Billing\\' => 'src/'],  // namespace => directory (relative to the package)
       'provider' => Acme\Billing\BillingServiceProvider::class, // or a list of providers
   ];
   ```
2. Create the service provider in `src/` (extends `Illuminate\Support\ServiceProvider`) and load routes,
   migrations, config and views from it with the usual `loadRoutesFrom()`, `loadMigrationsFrom()`, etc.
3. Keep `index.php` values plain (strings, arrays): `packages:cache` refuses closures and objects.
4. Optionally add the namespace to the app's `composer.json` PSR-4 map so IDEs, PHPStan and opcache preloading see
   the classes. The runtime autoloader works without it.
5. If the package scan is cached, run `php artisan packages:clear` (or `packages:cache`) so the new package is seen.

## Load order and `require`

- Discovery is sorted, and packages load in topological order of `require` (ties by id). Never depend on directory
  order; declare the dependency in `require` instead.
- Forced packages load during `register()`, enabled packages during `boot()`, after all forced ones.
- A forced package may require only forced packages. An enabled package may require forced packages and other
  enabled packages.
- An unmet requirement skips the package: it appears in `PackageManager::unavailable()` with `id`, `error` and
  `missing`, and a `PackageDependencyException` is reported. With `'strict' => true` it is thrown at boot.
- A cycle in `require` always throws `PackageDependencyException`.

## Inspecting and debugging

```bash
php artisan packages:list          # id, root, version, enabled (forced/yes/no), loaded, why unavailable
php artisan packages:list --json
```

In code, use `AminulBD\Package\Laravel\PackageManager` (a singleton):
`all()`, `get($id)`, `loaded()` (ids in load order), `isLoaded($id)`, `requires($id)`, `sort($ids)`,
`unavailable()`, `filterBy($rootNames)`.

Common problems:
- **Package not loading:** check `packages:list`. `enabled = no` means the activation handler didn't return its id;
  a message in *Unavailable* names the missing requirement or the broken `index.php`.
- **New package or changed `index.php` ignored:** the scan is cached. Run `php artisan packages:clear`.
- **Enabling a package has no effect:** it takes effect on the next boot. Restart long-running workers (Octane,
  Horizon, queue workers).

## Production

Run `php artisan packages:cache` on deploy (it is included in `php artisan optimize` on Laravel 11.27+). The
cache stores the discovered packages, not which ones are enabled, so toggling add-ons still works with the cache.
