# Laravel Packages
 
The **laravel-packages** package provides a straightforward solution for integrating modular and addon-based functionality into your Laravel application. This package allows you to organize your codebase by grouping related features or components into independent modules, making your application more maintainable, scalable, and easier to extend over time. Each module can have its own service providers, routes, controllers, views, migrations, and more (or a fullfeatured Laravel Application), ensuring clean separation of concerns within your application.

### Key Features:
- **Modular Architecture:** This package enables the separation of concerns by allowing you to treat different features or functionalities of your Laravel application as independent modules. Each module is self-contained with its own service providers, routes, controllers, views, migrations, and more. Also you can use a normal Laravel application as a package.
  
- **Dynamic Package Registration:** Easily register and load modules dynamically at runtime. You can manage which modules are enabled or disabled via configuration, providing flexible control over which parts of the app are active at any given time.

- **Service Providers & Schedules:** Automatically register the service providers for each module and load their scheduled tasks. This ensures that modules are fully integrated into the Laravel lifecycle and their tasks are executed properly.


### Use Cases:
- **Modular App Design:** When building large-scale Laravel applications, especially those with multiple features or domains, you can separate the logic into independent modules. This helps keep the codebase clean, organized, and easier to maintain.

- **Scalable Development:** By breaking the application into modules, development can be more scalable. Different teams can work on separate modules independently without interfering with the main application. You can even enable or disable certain features based on the environment or specific needs.

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
    // Service provider class of the package which must extends laravel's original service provider.
    'provider' => [
        YourDomain\Sample\SampleServiceProvider::class
    ], // also you can pass as string,

    'schedules' => [
        // add your schedule file path
    ]
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