# Changelog

All notable changes to `aminulbd/laravel-packages` are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

The public API is backward compatible: existing `config/packages.php` files, `index.php` files, activation
handlers and `PackageManager` methods keep working. The behaviour changes are listed under **Changed**.

### Fixed
- **Autoloader leak:** `PackageManager::load()` registered a new `spl_autoload_register()` closure per namespace on
  every application boot and never removed it. Processes that boot the app many times (test suites, Octane, queue
  workers) grew the autoloader stack on each boot and kept every old `PackageManager` in memory. One process-wide
  `PackageAutoloader` now serves every package namespace.
- **Nondeterministic load order:** `glob(..., GLOB_NOSORT)` returned packages in filesystem order, which differs
  between macOS (APFS) and Linux (ext4). Discovery is now sorted.
- **Undefined array key "forced":** a root without a `forced` key raised a PHP warning in `boot()` (an
  `ErrorException` under Laravel's error handler). It is now treated as `forced => false`. A root without a
  `location` is ignored, and a missing `roots` key no longer fails.

### Added
- **Dependency order:** packages are loaded, and their providers registered, in topological order of `require`
  (ties by id). A cycle throws `PackageDependencyException`.
- **`require` enforcement:** a package whose requirements are not loaded is skipped, listed in `unavailable()` (with
  `id` and `missing`) and reported. `config('packages.strict') = true` throws `PackageDependencyException` instead.
- **Package cache:** `php artisan packages:cache` / `packages:clear` write and remove
  `bootstrap/cache/laravel-packages.php` (path: `config('packages.cache')`). They are part of `optimize` /
  `optimize:clear` on Laravel 11.27+.
- **`php artisan packages:list [--json]`:** id, root, version, enabled, loaded and the reason a package is unavailable.
- `PackageManager::loaded()`, `isLoaded()`, `requires()`, `sort()`, `resolve()`, `discover()`, `registerManifest()`.
- `PackageServiceProvider::roots()`, `paths()`, `enabled()`, `manifestPath()`.
- `provider` in `index.php` may be a list of providers.
- Orchestra Testbench test suite (`composer test`).
- Agent skill `resources/boost/skills/laravel-packages-development/SKILL.md` (auto-discovered by Laravel Boost).

### Changed
- `load()` loads the given packages in dependency order, not in the order given.
- Activation handler classes are resolved from the container (constructor injection works).
- `composer.json` no longer hard-codes `"version"`; the version comes from git tags.

## [0.0.2]
- Seeders and factories in the sample package.

## [0.0.1]
- Initial release.
