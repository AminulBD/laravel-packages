<?php

namespace AminulBD\Package\Laravel\Console;

use AminulBD\Package\Laravel\PackageManager;
use AminulBD\Package\Laravel\PackageManifest;
use AminulBD\Package\Laravel\PackageServiceProvider;
use Illuminate\Console\Command;

class CacheCommand extends Command
{
    protected $signature = 'packages:cache';

    protected $description = 'Cache the package scan so the application does not glob and include every index.php on boot';

    public function handle(PackageManifest $manifest): int
    {
        $paths = PackageServiceProvider::paths(PackageServiceProvider::roots());
        // Always scan fresh: the running app may itself have been booted from an older manifest.
        $discovered = (new PackageManager())->discover($paths);

        try {
            $manifest->write($paths, $discovered);
        } catch (\LogicException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Cached %d package(s) to %s.', count($discovered['packages']), $manifest->path()));
        foreach ($discovered['unavailable'] as $entry) {
            $this->warn("Unavailable: {$entry['file']}: {$entry['error']}");
        }

        return self::SUCCESS;
    }
}
