<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel\Console;

use Illuminate\Console\Command;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

/**
 * Artisan wrapper over MetadataRegistry::clearCache() — run whenever DTO
 * classes or their attributes change between deploys, since a stale
 * `.meta.php` cache entry keeps serving the old compiled shape otherwise.
 */
final class ClearCommand extends Command
{
    protected $signature = 'sdo:clear';

    protected $description = 'Clear the in-memory and persisted metadata/hydrator/serializer cache for all BaseData classes';

    public function handle(): int
    {
        MetadataRegistry::clearCache();

        $this->components->info('Data object metadata cache cleared.');

        return self::SUCCESS;
    }
}
