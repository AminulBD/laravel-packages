# laravel-packages

A simple Laravel package that provides a way to make laravel more modular and extendable.

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

A sample extension is provided in the `/packages` directory. You can create your own package in the same way.

## Creating an Extension

To create an extension, you need to create a directory in the `/packages` directory and then create a `index.php` file
in it.

Here is a sample `/packages/YOUR_PACKAGE/index.php` file:

```php
<?php

return [
    'id' => 'yourdomain.sample', // Unique ID of the extension
    'autoload' => [
        'YourDomain\\Sample\\' => 'src/',
    ],
    // Service provider class of the extension which must extends laravel's original service provider.
    'provider' => YourDomain\Sample\SampleServiceProvider::class,
];
```

Now create a `SampleServiceProvider.php` file in the `/packages/YOUR_PACKAGE/src` directory of the extension and extend the
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

That's it. You have created an extension. Now you can use the extension in your laravel project.

You can customize anything you do with your laravel project. You will get the full power of laravel in your extension
because we are using laravel's service provider to register the extension.

## Handle Extension Activations

The power of this package is you can activate or deactivate the extensions from the admin panel. But you have to build
that feature yourself. For now, you have 2-different way to handle it. Either you can use the `config/packages.php` and
edit
`enabled` value as array of id of the extensions you want to enable. Or you can use the pass a class that implements the
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
        return \App\Models\ActivatedExtensions::pluck('id')->toArray();
    }
}
```
