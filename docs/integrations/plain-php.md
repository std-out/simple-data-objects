# Plain PHP

The library is framework-agnostic: it needs no container, no kernel, no service provider. `composer require std-out/simple-data-objects` and everything works — including validation.

## Bootstrap

The only production setup worth doing is enabling the file cache once at startup:

```php
require __DIR__.'/vendor/autoload.php';

use StdOut\SimpleDataObjects\Support\MetadataRegistry;

MetadataRegistry::setStoragePath(__DIR__.'/var/cache/data-objects');
```

Without it everything still works — metadata is built once per process and kept in memory.

## Everyday use

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Attributes\Rules;

class SignupData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string', 'max:100'])]
        public readonly string $name,
        #[Rules(['required', 'email'])]
        public readonly string $email,
    ) {}
}

// from any source — array, JSON string, object, iterator
$signup = SignupData::from($_POST);
$signup = SignupData::from(file_get_contents('php://input')); // JSON body
```

## Standalone validation

Validation uses `illuminate/validation` under the hood but **does not need a Laravel application** — a self-contained validator factory is created automatically:

```php
SignupData::validate($_POST);              // throws ValidationException
$signup = SignupData::fromValidated($_POST); // validate + hydrate
```

To plug in your own translator/messages, provide a factory once:

```php
use StdOut\SimpleDataObjects\BaseData;

BaseData::setValidatorFactory($myValidatorFactory);
```

## Deploy

```sh
vendor/bin/sdo-warm var/cache/data-objects src/Data
```

Then optionally add the cache files to [opcache.preload](../features/cache.md#going-further-opcachepreload) — a fresh worker's first request pays nothing.
