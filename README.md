# Laravel Packages

*A simple Laravel package that provides a way to make Laravel more modular and extensible.*

## Introduction

The **laravel-packages** package allows you to create modular packages within your Laravel application. This helps in organizing your codebase by grouping related functionalities into separate packages, making your application more maintainable and scalable. Each package can have its own routes, controllers, views, migrations, and more.

## Requirements

- **Laravel** 9.x or higher
- **PHP** 8.0 or higher

## Installation

You can install the package via Composer:

```bash
# Install the package
composer require aminulbd/laravel-packages

# Publish the configuration file and sample packages
php artisan vendor:publish --tag=laravel-packages
```

The `vendor:publish` command will publish the configuration file `config/packages.php` and a sample package in the `/packages` directory.

## Configuration

Open the `config/packages.php` file and update the `roots` array with the paths where your packages are located. By default, the configuration is set to use the `/packages` directory of your Laravel project, but you can change or add as many paths as you need.

```php
return [
    'roots' => [
        'default' => [
            'forced' => false, // force enable all packages inside this location (omitted = false).
            'location' => '/packages',
        ],
        // More paths...
    ],

    'enabled' => [/* package ids, see "Handling Package Activation" */],

    // Unmet `require`: true = throw, false = skip the package and report it (see "Dependencies and load order").
    'strict' => false,

    // Where `php artisan packages:cache` writes the manifest (null = bootstrap/cache/laravel-packages.php).
    'cache' => null,
];
```

| Key | Default | Meaning |
|---|---|---|
| `roots.*.location` | — | Directory (relative to the project's base path) whose sub-directories are packages. A root without `location` is ignored. |
| `roots.*.forced` | `false` | `true` loads every package of the root on every boot. `false` loads only the ids returned by `enabled`. |
| `enabled` | `[]` | Ids of packages to load from non-forced roots: an array, a callable or a `PackageActivationHandler` class. |
| `strict` | `false` | What happens when a package's `require` is not met (see below). |
| `cache` | `null` | Path of the cached package manifest. |

## Creating a Package

To create a package, follow these steps:

1. **Create a Package Directory**: Create a new directory for your package inside one of the paths specified in the `roots` array. For example, create `/packages/YourPackage`.

2. **Create an Index File**: Inside your package directory, create an `index.php` file. This file will return an array with package configurations.

    Example `/packages/YourPackage/index.php`:

    ```php
    <?php

    return [
        'id' => 'yourdomain.yourpackage', // Unique ID of the package
        'autoload' => [
            'YourDomain\\YourPackage\\' => 'src/',
        ],
        // Service provider class of the package, must extend Laravel's ServiceProvider
        'provider' => YourDomain\YourPackage\YourPackageServiceProvider::class,
    ];
    ```

3. **Set Up Autoloading**: The `autoload` key maps your package's namespace to its source directory. This allows Laravel to autoload your package classes.

4. **Create a Service Provider**: In your package's `src` directory, create a service provider class that extends `Illuminate\Support\ServiceProvider`.

    Example `/packages/YourPackage/src/YourPackageServiceProvider.php`:

    ```php
    <?php

    namespace YourDomain\YourPackage;

    use Illuminate\Support\ServiceProvider;

    class YourPackageServiceProvider extends ServiceProvider
    {
        /**
         * Register any application services.
         *
         * @return void
         */
        public function register()
        {
            // Register bindings in the container.
        }

        /**
         * Bootstrap any application services.
         *
         * @return void
         */
        public function boot()
        {
            // Register routes, views, translations, and other package resources.
        }
    }
    ```

5. **Add Package Functionality**: Add your package's routes, controllers, views, migrations, etc., within the package's directory structure.

## Using the Package

Once your package is set up, Laravel will automatically load it according to the configurations provided. You can use all Laravel features within your package, including routing, controllers, views, and more.

For example, to add routes in your package, you can create a `routes` directory and define your routes in a `web.php` file:

```php
// File: /packages/YourPackage/routes/web.php

<?php

use Illuminate\Support\Facades\Route;

Route::get('/your-package', function () {
    return 'Hello from YourPackage!';
});
```

Then, in your `YourPackageServiceProvider`, load the routes:

```php
public function boot()
{
    $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

    // Load other resources like views, migrations, etc.
}
```

## Handling Package Activation

One of the powerful features of the **laravel-packages** package is the ability to activate or deactivate packages, for instance, from your application's admin panel. To handle package activation, you have two options:

### 1. Using the Configuration File

Edit the `enabled` key in the `config/packages.php` file and set its value as an array of IDs representing the packages you want to enable.

```php
return [
    // ...
    'enabled' => [
        'yourdomain.yourpackage',
        // Add other package IDs to enable
    ],
];
```

### 2. Using a Custom Activation Handler

Implement a class that implements the `\AminulBD\Package\Laravel\PackageActivationHandler` interface. This allows you to dynamically determine which packages are enabled, e.g., based on database records.

Example implementation:

```php
<?php

namespace App\Services;

use AminulBD\Package\Laravel\PackageActivationHandler;

class Activator implements PackageActivationHandler
{
    /**
     * Get the list of enabled package IDs.
     *
     * @return array
     */
    public function enabled(): array
    {
        // Fetch the enabled package IDs from the database or other storage
        return \App\Models\ActivatedPackage::pluck('id')->toArray();
    }
}
```

Then, update your `config/packages.php` to use the custom activation handler:

```php
return [
    //...
    'enabled' => App\Services\Activator::class,
];
```

## Dependencies and load order

A package can declare the packages it needs with `require`:

```php
return [
    'id' => 'acme.billing',
    'require' => ['acme.kernel', 'acme.users'],
    'autoload' => ['Acme\\Billing\\' => 'src/'],
    'provider' => Acme\Billing\BillingServiceProvider::class,
];
```

**Load order is deterministic.** Index files are discovered in sorted (byte) order, so macOS, Linux and Windows
see the same order. Packages are then loaded, and their service providers registered, in topological order of
`require`: a package always comes after the packages it requires. Ties are broken by id. Forced roots load during
`register()`; enabled packages from other roots load during `boot()`, after every forced package.

**`require` is enforced.** Before a package is loaded, every id in its `require` must be either already loaded or
loaded in the same batch:

- a package in a **forced** root may require other forced packages;
- an **enabled** package may require forced packages and other enabled packages.

If a requirement is missing (not installed, not enabled, or itself unavailable), the package is not loaded:

- with `'strict' => false` (default) it is listed in `PackageManager::unavailable()` with `id`, `error` and
  `missing`, and a `PackageDependencyException` is passed to `report()` (your log / error tracker);
- with `'strict' => true` the `PackageDependencyException` is thrown during boot. Useful in `local` and `testing`:
  `'strict' => in_array(env('APP_ENV'), ['local', 'testing'])`.

A dependency cycle always throws `PackageDependencyException`.

`PackageManager` also exposes the graph: `loaded()` (ids in load order), `isLoaded($id)`, `requires($id)`,
`sort($ids)` (topological order) and `resolve($ids, $available, $strict)` (the enforcement above).

## Caching the package scan

Without a cache every boot globs each root and `include`s every `index.php`. In production, cache it like the
config:

```bash
php artisan packages:cache   # writes bootstrap/cache/laravel-packages.php
php artisan packages:clear   # removes it
```

On Laravel 11.27+ both run as part of `php artisan optimize` / `optimize:clear`.

- The manifest records the root paths it was built from. If the roots change, or the application moves to another
  directory, it is ignored and the scan runs as usual. Run `packages:cache` on the machine (or image) that serves
  the app, after deploying.
- While the cache exists, new packages, removed packages and changes to `index.php` files are **not** seen. Run
  `packages:clear` (or `packages:cache` again) after changing them.
- Which packages are *enabled* is never cached: the activation handler still runs on every boot.
- `index.php` values must be plain PHP values (strings, numbers, arrays). `packages:cache` fails on closures or
  objects.

## Inspecting packages

```bash
php artisan packages:list          # table
php artisan packages:list --json   # machine-readable
```

It lists every discovered package with its root, version, whether it is enabled (`forced`, `yes`, `no`), whether it
is loaded, and why it is unavailable (invalid `index.php`, missing requirement). It also says whether the package
scan is cached.

## Autoloading

Each package's `autoload` map (PSR-4 style, namespace => directory relative to the package) is served by one
process-wide autoloader (`PackageAutoloader`). Booting the application again in the same process (test suites,
Octane, queue workers) re-uses it instead of registering more autoloaders. The longest matching namespace prefix
wins. You can still add the namespaces to your `composer.json` PSR-4 map for IDEs and static analysis.

## Publishing Package Resources

If your package contains resources that need to be published to the main application (like views, configurations, assets), you can use Laravel's publishing mechanism.

In your package's service provider, add:

```php
public function boot()
{
    // ...

    // Publish package configurations
    $this->publishes([
        __DIR__.'/../config/yourpackage.php' => config_path('yourpackage.php'),
    ], 'config');

    // Publish package views
    $this->loadViewsFrom(__DIR__.'/../resources/views', 'yourpackage');
}
```

Consumers of your package can then publish these resources using:

```bash
php artisan vendor:publish --tag=yourpackage
```

## AI agent skill

The package ships an agent skill at
[`resources/boost/skills/laravel-packages-development/SKILL.md`](resources/boost/skills/laravel-packages-development/SKILL.md):
how to create a package, `require` and load order, activation, caching and debugging with `packages:list`.

- With [Laravel Boost](https://github.com/laravel/boost), `php artisan boost:install` picks it up automatically
  (third-party packages' `resources/boost/skills`).
- For other agents, copy the directory into the project's skills folder (e.g. `.claude/skills/`).

## Testing

```bash
composer install
composer test   # PHPUnit + Orchestra Testbench
```

## Conclusion

The **laravel-packages** package makes it easy to create modular, self-contained packages within your Laravel application. By organizing your code into packages, you can improve maintainability, encourage code reuse, and make your application more scalable.

For more information and advanced usage, please refer to the package's repository and Laravel's official documentation on service providers and package development.