<?php

namespace AminulBD\Package\Laravel\Console;

use AminulBD\Package\Laravel\PackageManifest;
use Illuminate\Console\Command;

class ClearCommand extends Command
{
    protected $signature = 'packages:clear';

    protected $description = 'Remove the cached package scan';

    public function handle(PackageManifest $manifest): int
    {
        $manifest->delete();

        $this->info('Package cache cleared.');

        return self::SUCCESS;
    }
}
