# laravel-packages

The **laravel-packages** package provides a straightforward solution for integrating modular and addon-based functionality into your Laravel application. This package allows you to organize your codebase by grouping related features or components into independent modules, making your application more maintainable, scalable, and easier to extend over time. Each module can have its own service providers, routes, controllers, views, migrations, and more (or a fullfeatured Laravel Application), ensuring clean separation of concerns within your application.

### Key Features:
- **Modular Architecture:** This package enables the separation of concerns by allowing you to treat different features or functionalities of your Laravel application as independent modules. Each module is self-contained with its own service providers, routes, controllers, views, migrations, and more. Also you can use a normal Laravel application as a package.
  
- **Dynamic Package Registration:** Easily register and load modules dynamically at runtime. You can manage which modules are enabled or disabled via configuration, providing flexible control over which parts of the app are active at any given time.

- **Service Providers & Schedules:** Automatically register the service providers for each module and load their scheduled tasks. This ensures that modules are fully integrated into the Laravel lifecycle and their tasks are executed properly.


### Use Cases:
- **Modular App Design:** When building large-scale Laravel applications, especially those with multiple features or domains, you can separate the logic into independent modules. This helps keep the codebase clean, organized, and easier to maintain.

- **Scalable Development:** By breaking the application into modules, development can be more scalable. Different teams can work on separate modules independently without interfering with the main application. You can even enable or disable certain features based on the environment or specific needs.


## Installation

You can install the package via composer:

```bash
# Install the package
composer require aminulbd/laravel-packages

# Publish the config file and sample packages
php artisan vendor:publish --tag=laravel-packages
```

## Usage

Open the `config/packages.php` file and update the `roots` array with the paths where your packages are located. by
default, the config is set to `/packages` directory of your laravel project. But you can change or add as many as you
want.

A sample package is provided in the `/packages` directory. You can create your own package in the same way.

## Creating a Package

To create an package, you need to create a directory in the `/packages` directory and then create a `index.php` file
in it.

Here is a sample `/packages/YOUR_PACKAGE/index.php` file:

```php
<?php

return [
    'id' => 'yourdomain.sample', // Unique ID of the package
    'autoload' => [
        'YourDomain\\Sample\\' => 'src/',
    ],
    // Service provider class of the package which must extends laravel's original service provider.
    'provider' => [
        YourDomain\Sample\SampleServiceProvider::class
    ], // also you can pass as string,

    'schedules' => [
        // add your schedule file path
    ],
];
```

Now create a `SampleServiceProvider.php` file in the `/packages/YOUR_PACKAGE/src` directory of the package and extend the
`ServiceProvider` class of laravel.

For example:

```php
<?php

namespace YourDomain\Sample;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SampleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // register views, migration, commands, routes etc.
    }
}
```

That's it. You have created an package. Now you can use the package in your laravel project.

You can customize anything you do with your laravel project. You will get the full power of laravel in your package
because we are using laravel's service provider to register the package.

## Handle Packages Activations

The power of this package is you can activate or deactivate the packages from the admin panel. But you have to build
that feature yourself. For now, you have 2-different way to handle it. Either you can use the `config/packages.php` and
edit
`enabled` value as array of id of the packages you want to enable. Or you can use the pass a class that implements the
`\AminulBD\Package\Laravel\PackageActivationHandler` interface.

Here is an example of the `PackageActivationHandler`:

```php
<?php

namespace App\Services;

use AminulBD\Package\Laravel\PackageActivationHandler;

class Activator implements PackageActivationHandler
{

    public function enabled(): array
    {
        return \App\Models\ActivatedPackage::pluck('id')->toArray();
    }
}
```
