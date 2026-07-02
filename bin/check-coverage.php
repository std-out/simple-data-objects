<?php

declare(strict_types=1);

$file = $argv[1] ?? 'build/coverage.xml';
$threshold = (float) ($argv[2] ?? 95);

if (! file_exists($file)) {
    fwrite(STDERR, "Coverage file not found: {$file}\n");
    exit(1);
}

$xml = simplexml_load_file($file);
$metrics = $xml->project->metrics;
$total = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$pct = $total > 0 ? round($covered / $total * 100, 2) : 0.0;

echo "Coverage: {$pct}%\n";

if ($pct < $threshold) {
    fwrite(STDERR, "Coverage {$pct}% is below the required {$threshold}%\n");
    exit(1);
}
