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
            'forced' => false, // force enable all packages inside this location.
            'location' => '/packages',
        ],
        // More paths...
    ],
    // Other configurations...
];
```

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

## Conclusion

The **laravel-packages** package makes it easy to create modular, self-contained packages within your Laravel application. By organizing your code into packages, you can improve maintainability, encourage code reuse, and make your application more scalable.

For more information and advanced usage, please refer to the package's repository and Laravel's official documentation on service providers and package development.