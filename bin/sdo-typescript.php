<?php

declare(strict_types=1);

// Locate the autoloader. Order matters: composer's bin proxy provides the
// exact path; otherwise prefer the project the command is run FROM (cwd) —
// with a path-repository symlink __DIR__ resolves inside the package source,
// where the relative guesses would pick the wrong vendor tree.
$candidates = [
    $_composer_autoload_path ?? null,
    getcwd().'/vendor/autoload.php',
    __DIR__.'/../../../autoload.php',
    __DIR__.'/../vendor/autoload.php',
];

foreach ($candidates as $autoload) {
    if ($autoload !== null && is_file($autoload)) {
        require $autoload;
        break;
    }
}

use StdOut\SimpleDataObjects\Support\CacheWarmer;
use StdOut\SimpleDataObjects\Support\TypeScriptGenerator;

$args = array_slice($argv, 1);

if ($args === []) {
    fwrite(STDERR, "Generates a TypeScript .d.ts file with one `interface` per concrete\n");
    fwrite(STDERR, "BaseData subclass (and one `type` union per #[Discriminator] parent)\n");
    fwrite(STDERR, "found in the scanned sources.\n\n");
    fwrite(STDERR, "Usage: sdo-typescript <output.d.ts> [<src-path> ...]\n\n");
    fwrite(STDERR, "Without <src-path> arguments, the PSR-4 directories from ./composer.json\n");
    fwrite(STDERR, "are scanned.\n\n");
    fwrite(STDERR, "Example: vendor/bin/sdo-typescript resources/js/types/data-objects.d.ts app/Data\n");
    exit(2);
}

$output = array_shift($args);

if ($args === []) {
    $args = CacheWarmer::pathsFromComposer(getcwd().'/composer.json');

    if ($args === []) {
        fwrite(STDERR, "No source paths given and no PSR-4 autoload directories found in ./composer.json.\n");
        exit(2);
    }

    echo 'Scanning PSR-4 paths from composer.json: '.implode(', ', $args)."\n\n";
}

try {
    $classes = CacheWarmer::discover($args);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: '.$e->getMessage()."\n");
    exit(1);
}

if ($classes === []) {
    fwrite(STDERR, 'WARNING: no concrete BaseData subclasses found under: '.implode(', ', $args)."\n");
    fwrite(STDERR, "Check that the classes are autoloadable from the current working directory.\n");
}

try {
    $contents = TypeScriptGenerator::generate($classes);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: '.$e->getMessage()."\n");
    exit(1);
}

$dir = dirname($output);

if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
    fwrite(STDERR, "ERROR: could not create directory: {$dir}\n");
    exit(1);
}

if (file_put_contents($output, $contents) === false) {
    fwrite(STDERR, "ERROR: could not write to: {$output}\n");
    exit(1);
}

foreach ($classes as $class) {
    echo "  generated  {$class}\n";
}

printf("\n%d class(es) written to %s\n", count($classes), $output);

exit(0);
