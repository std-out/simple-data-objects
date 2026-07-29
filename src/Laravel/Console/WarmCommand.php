<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel\Console;

use Illuminate\Console\Command;
use StdOut\SimpleDataObjects\Support\CacheWarmer;

/**
 * Artisan wrapper over CacheWarmer — the same discovery/persistence logic
 * used by the standalone bin/sdo-warm binary, wired to the package config
 * instead of CLI arguments so it can join `php artisan optimize`.
 */
final class WarmCommand extends Command
{
    protected $signature = 'sdo:warm {paths?* : Directories or files to scan; defaults to config(simple-data-objects.paths)} {--cache= : Overrides config(simple-data-objects.cache_path)}';

    protected $description = 'Pre-build the metadata + compiled hydrator/serializer cache for every BaseData subclass';

    public function handle(): int
    {
        $cachePath = $this->option('cache') ?? config('simple-data-objects.cache_path');

        if ($cachePath === null) {
            $this->components->error('No cache path configured. Set config(simple-data-objects.cache_path) or pass --cache=DIR.');

            return self::FAILURE;
        }

        /** @var list<string> $paths */
        $paths = $this->argument('paths') ?: config('simple-data-objects.paths', []);

        if ($paths === []) {
            $this->components->error('No source paths given and config(simple-data-objects.paths) is empty.');

            return self::FAILURE;
        }

        $result = CacheWarmer::warm($cachePath, $paths);

        if ($result['warmed'] === [] && $result['skipped'] === []) {
            $this->components->warn('No concrete BaseData subclasses found under: '.implode(', ', $paths));
        }

        foreach ($result['warmed'] as $class) {
            $this->components->twoColumnDetail($class, '<fg=green>warmed</>');
        }

        foreach ($result['skipped'] as $class) {
            $this->components->twoColumnDetail($class, '<fg=yellow>skipped</> (metadata not exportable)');
        }

        $this->components->info(sprintf(
            '%d class(es) warmed, %d skipped → %s',
            count($result['warmed']),
            count($result['skipped']),
            $cachePath,
        ));

        return self::SUCCESS;
    }
}
