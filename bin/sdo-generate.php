<?php

declare(strict_types=1);

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

use Illuminate\Support\Str;
use StdOut\SimpleDataObjects\Support\DataClassGenerator;
use StdOut\SimpleDataObjects\Support\SampleReader;

$options = ['class' => null, 'namespace' => null, 'xml-path' => null, 'force' => false, 'dry-run' => false, 'optional' => false];
$positional = [];

foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--(class|namespace|xml-path)=(.+)$/', $arg, $m) === 1) {
        $options[$m[1]] = $m[2];
    } elseif (in_array($arg, ['--force', '--dry-run', '--optional'], true)) {
        $options[substr($arg, 2)] = true;
    } elseif (str_starts_with($arg, '--')) {
        fwrite(STDERR, "Unknown option: {$arg}\n");
        exit(2);
    } else {
        $positional[] = $arg;
    }
}

if (count($positional) !== 2) {
    fwrite(STDERR, "Generates data object classes from a sample JSON or XML document: one class\n");
    fwrite(STDERR, "per object shape, with property types, nested DTOs and collections inferred\n");
    fwrite(STDERR, "from the values. Review the result — a sample only shows what it contains.\n\n");
    fwrite(STDERR, "Usage: sdo-generate <sample.json|sample.xml|-> <output-dir> [options]\n\n");
    fwrite(STDERR, "  -                        read the sample from STDIN\n");
    fwrite(STDERR, "  --class=OrderData        root class name (default: from the file or XML element name)\n");
    fwrite(STDERR, "  --namespace=App\\Data     namespace (default: from the PSR-4 map in ./composer.json)\n");
    fwrite(STDERR, "  --xml-path=catalog/offer path to the record element (default: the repeated child\n");
    fwrite(STDERR, "                           of the root element, or the root element itself)\n");
    fwrite(STDERR, "  --optional               keys missing from some objects become T|Optional (omitted\n");
    fwrite(STDERR, "                           again by toArray()) instead of ?T = null\n");
    fwrite(STDERR, "  --dry-run                print the classes instead of writing them\n");
    fwrite(STDERR, "  --force                  overwrite existing files\n\n");
    fwrite(STDERR, "Examples: vendor/bin/sdo-generate storage/order.json app/Data\n");
    fwrite(STDERR, "          curl -s https://api.example.com/orders/1 | vendor/bin/sdo-generate - app/Data --class=OrderData\n");
    exit(2);
}

[$sample, $outputDir] = $positional;

$identifier = '/^[A-Za-z_][A-Za-z0-9_]*$/';
$namespace = $options['namespace'] ?? DataClassGenerator::namespaceFor(getcwd().'/composer.json', $outputDir);

if ($namespace === null) {
    fwrite(STDERR, "ERROR: {$outputDir} is not under a PSR-4 directory of ./composer.json; pass --namespace.\n");
    exit(2);
}

foreach (explode('\\', $namespace) as $segment) {
    if (preg_match($identifier, $segment) !== 1) {
        fwrite(STDERR, "ERROR: invalid namespace: {$namespace}\n");
        exit(2);
    }
}

$contents = $sample === '-' ? stream_get_contents(STDIN) : (is_file($sample) ? file_get_contents($sample) : false);

if ($contents === false) {
    fwrite(STDERR, "ERROR: cannot read sample file: {$sample}\n");
    exit(1);
}

$isXml = strtolower(pathinfo($sample, PATHINFO_EXTENSION)) === 'xml' || str_starts_with(ltrim($contents), '<');

if ($isXml && $sample === '-') {
    $sample = (string) tempnam(sys_get_temp_dir(), 'sdo');
    file_put_contents($sample, $contents);
    register_shutdown_function(static fn () => @unlink($sample));
}

try {
    if ($isXml) {
        [$type, $path] = SampleReader::fromXml($sample, $options['xml-path']);
        $default = Str::studly(basename($path)).'Data';
    } else {
        $type = SampleReader::fromJson($contents);
        $name = $sample === '-' ? 'root' : pathinfo($sample, PATHINFO_FILENAME);
        $default = Str::studly($type->list ? Str::singular($name) : $name).'Data';
    }

    $class = $options['class'] ?? $default;

    if (preg_match($identifier, $class) !== 1) {
        fwrite(STDERR, "ERROR: invalid class name: {$class}\n");
        exit(2);
    }

    $classes = DataClassGenerator::generate($type, $class, $namespace, $isXml, $options['optional']);
} catch (InvalidArgumentException $e) {
    fwrite(STDERR, 'ERROR: '.$e->getMessage()."\n");
    exit(1);
}

if ($options['dry-run']) {
    foreach ($classes as $name => $source) {
        echo "// {$outputDir}/{$name}.php\n{$source}\n";
    }

    exit(0);
}

try {
    DataClassGenerator::write($classes, $outputDir, $options['force']);
} catch (InvalidArgumentException $e) {
    fwrite(STDERR, 'ERROR: '.$e->getMessage()."\n");
    exit(1);
}

foreach (array_keys($classes) as $name) {
    echo "  generated  {$namespace}\\{$name}\n";
}

$hydrate = $isXml ? "{$class}::lazyXml(\$file, '{$path}')" : "{$class}::from(\$data)";
printf("\n%d class(es) written to %s — hydrate with %s\n", count($classes), $outputDir, $hydrate);

exit(0);
