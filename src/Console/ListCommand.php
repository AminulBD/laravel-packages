<?php

namespace AminulBD\Package\Laravel\Console;

use AminulBD\Package\Laravel\PackageManager;
use AminulBD\Package\Laravel\PackageManifest;
use AminulBD\Package\Laravel\PackageServiceProvider;
use Illuminate\Console\Command;

class ListCommand extends Command
{
    protected $signature = 'packages:list {--json : Output the rows as JSON}';

    protected $description = 'List discovered packages: root, whether they are enabled and loaded, and why not';

    public function handle(PackageManager $manager, PackageManifest $manifest): int
    {
        $roots = PackageServiceProvider::roots();
        $enabled = PackageServiceProvider::enabled($this->laravel);
        $loaded = $manager->loaded();
        $problems = [];
        foreach ($manager->unavailable() as $entry) {
            if (isset($entry['id'])) {
                $problems[$entry['id']] = $entry['error'];
            }
        }

        $rows = [];
        foreach ($manager->all() as $id => $package) {
            $forced = $roots[$package['type']]['forced'] ?? false;
            $rows[] = [
                'id' => (string) $id,
                'root' => (string) $package['type'],
                'version' => (string) ($package['version'] ?? ''),
                'enabled' => $forced ? 'forced' : (in_array($id, $enabled, true) ? 'yes' : 'no'),
                'loaded' => in_array($id, $loaded, true) ? 'yes' : 'no',
                'unavailable' => $problems[$id] ?? '',
            ];
        }
        // Index files that could not be read have no id.
        foreach ($manager->unavailable() as $entry) {
            if (! isset($entry['id'])) {
                $rows[] = ['id' => '', 'root' => (string) $entry['type'], 'version' => '', 'enabled' => '', 'loaded' => 'no', 'unavailable' => $entry['file'].': '.$entry['error']];
            }
        }

        usort($rows, fn ($a, $b) => [$a['root'], $a['id']] <=> [$b['root'], $b['id']]);

        if ($this->option('json')) {
            $this->line((string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(['ID', 'Root', 'Version', 'Enabled', 'Loaded', 'Unavailable'], array_map('array_values', $rows));
        $this->line($manifest->exists() ? 'Package scan: cached ('.$manifest->path().')' : 'Package scan: not cached (run packages:cache)');

        return self::SUCCESS;
    }
}
