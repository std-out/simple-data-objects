<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel\Console;

use Illuminate\Console\Command;
use StdOut\SimpleDataObjects\Support\CacheWarmer;
use StdOut\SimpleDataObjects\Support\TypeScriptGenerator;

/**
 * Artisan wrapper over TypeScriptGenerator — the same discovery logic as
 * `sdo:warm` (CacheWarmer::discover()), wired to config instead of CLI
 * arguments, matching the standalone bin/sdo-typescript binary.
 */
final class TypeScriptCommand extends Command
{
    protected $signature = 'sdo:typescript {paths?* : Directories or files to scan; defaults to config(simple-data-objects.paths)} {--output= : Overrides config(simple-data-objects.typescript_output)}';

    protected $description = 'Generate a TypeScript .d.ts file with one interface per BaseData subclass';

    public function handle(): int
    {
        $output = $this->option('output') ?? config('simple-data-objects.typescript_output');

        if ($output === null) {
            $this->components->error('No output path configured. Set config(simple-data-objects.typescript_output) or pass --output=FILE.');

            return self::FAILURE;
        }

        /** @var list<string> $paths */
        $paths = $this->argument('paths') ?: config('simple-data-objects.paths', []);

        if ($paths === []) {
            $this->components->error('No source paths given and config(simple-data-objects.paths) is empty.');

            return self::FAILURE;
        }

        $classes = CacheWarmer::discover($paths);

        if ($classes === []) {
            $this->components->warn('No concrete BaseData subclasses found under: '.implode(', ', $paths));
        }

        $dir = dirname($output);

        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->components->error("Could not create directory: {$dir}");

            return self::FAILURE;
        }

        if (file_put_contents($output, TypeScriptGenerator::generate($classes)) === false) {
            $this->components->error("Could not write to: {$output}");

            return self::FAILURE;
        }

        foreach ($classes as $class) {
            $this->components->twoColumnDetail($class, '<fg=green>generated</>');
        }

        $this->components->info(sprintf('%d class(es) written to %s', count($classes), $output));

        return self::SUCCESS;
    }
}
