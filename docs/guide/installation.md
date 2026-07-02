# Installation

## Requirements

- PHP **8.4** or higher
- Composer

## Install via Composer

```bash
composer require std-out/simple-data-objects
```

## Laravel

No service provider or configuration needed. The library auto-discovers the validator from the Laravel container. Optional Laravel-specific methods (`fromRequest`, `fromModel`, `toResponse`) require:

```bash
composer require illuminate/http illuminate/database
```

## Standalone (without Laravel)

Works out of the box. When no Laravel container is present, a minimal validator is bootstrapped automatically with the default English messages.

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Attributes\Rules;

class ContactData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string'])]
        public readonly string $name,

        #[Rules(['required', 'email'])]
        public readonly string $email,
    ) {}
}

// Works without Laravel
ContactData::validate(['name' => 'Alice', 'email' => 'bad']); // throws ValidationException
```

## File-based Metadata Cache (optional)

For PHP-FPM environments where each request starts a fresh process, pre-compile metadata to files that opcache picks up:

```php
// bootstrap.php / AppServiceProvider
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

MetadataRegistry::setStoragePath(storage_path('framework/data-objects'));
```

Pre-warm the cache on deploy so the first request pays nothing (see [Metadata Cache](../features/cache.md#pre-warming-on-deploy)):

```bash
vendor/bin/sdo-warm storage/framework/data-objects app/Data
```

Clear on deploy:

```bash
php artisan tinker --execute="StdOut\SimpleDataObjects\Support\MetadataRegistry::clearCache()"
```
