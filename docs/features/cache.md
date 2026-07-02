# Metadata Cache

## How Metadata Works

The first time a DTO class is hydrated, the library uses PHP Reflection to read its constructor parameters and attributes. The result is stored as a `ClassMeta` object in a static in-memory cache (`MetadataRegistry`).

For subsequent calls in the **same PHP process**, reflection is skipped entirely. This covers long-running runtimes like **Laravel Octane**, **Swoole**, and **RoadRunner** with zero extra configuration.

On top of the metadata, `from()` and `toArray()` compile a **specialized closure per class** on first use (kept in memory for the process): plain properties become direct array reads/writes, and only properties with casts, enums, nested DTOs, or pipes go through the richer runtime path. Behavior is identical to the metadata-driven path — this is purely an execution-speed optimization.

## File-based Cache (PHP-FPM)

In traditional PHP-FPM environments, each request starts a fresh process. Enable the file cache to persist metadata between requests:

```php
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

MetadataRegistry::setStoragePath('/path/to/cache/dir');
```

On first access, a PHP file is written for each class. Subsequent requests `require` this file — opcache compiles and retains it — **no reflection happens at all**.

### Cache File Format

Cache files use `var_export()` with `__set_state()`, not `serialize()`, and carry the **compiled hydrator and serializer closures** alongside the metadata:

```php
<?php

$meta = \StdOut\SimpleDataObjects\Support\ClassMeta::__set_state([/* ... */]);
$p = $meta->parameters;
$pipes = $meta->pipes;

return [
    $meta,
    static function (array $d) use ($p, $pipes): \App\Data\UserData { /* compiled hydration */ },
    static function (\App\Data\UserData $o) use ($p): array { /* compiled serialization */ },
];
```

This means:
- **No deserialization gadget chains** — no `unserialize()` call
- **Opcache-friendly** — the whole file, closures included, is compiled to opcodes once; a warmed process pays neither reflection nor closure compilation
- **Human-readable** — easy to inspect during debugging

### File Naming

Cache files are named `sha256(classname).meta.php`. There is no path traversal risk regardless of class naming, and the distinct `.meta.php` suffix guarantees cache clearing can never touch foreign files.

## Pre-warming on Deploy

Instead of letting the first request pay the build cost, generate the cache ahead of time with the bundled CLI:

```sh
# scan specific paths
vendor/bin/sdo-warm storage/framework/cache/data-objects app/Data

# or scan all PSR-4 directories from your composer.json automatically
vendor/bin/sdo-warm storage/framework/cache/data-objects
```

It scans the sources for **concrete** `BaseData` subclasses (abstract bases are skipped), builds each class's metadata **and compiled closures**, and writes the cache files. Add it to your deploy script right before the app goes live — every FPM worker then starts fully warm.

Classes whose metadata cannot be exported (see [Limitations](#limitations)) are reported as `skipped` and keep using the in-memory cache. A broken DTO definition (e.g. conflicting attributes) fails the command immediately — deploy-time is exactly when you want to find out.

## Clearing the Cache

```php
MetadataRegistry::clearCache();
```

Clears both in-memory and on-disk cache. Run this after deploying new DTO classes or after modifying attributes. Only files matching `*.meta.php` are deleted — even if the storage path accidentally points at a shared directory, other `.php` files are safe.

## Limitations

Classes whose metadata includes non-exportable objects (Laravel `Rule` instances, closures) fall back to in-memory cache only. String rules are always cacheable.

Classes using [`EncryptedCast`](../casts/encrypted.md) also fall back to in-memory cache only — by design: persisting the cast would write its key material to disk in plaintext.

```php
// ✅ Cacheable
#[Rules(['required', 'email', 'max:100'])]

// ⚠️ Falls back to memory-only cache
#[Rules([new Password->min(8)->letters()])]
```

## Custom Casts and Caching

Custom casts must implement `__set_state()` to be included in the file cache:

```php
final class MoneyCast implements CastsValue
{
    public function __construct(
        public readonly string $currency = 'USD',
    ) {}

    public static function __set_state(array $state): self
    {
        return new self($state['currency']);
    }

    // ...
}
```

::: warning Note on visibility
Properties used in `__set_state()` must be **public** or accessible via the state array. PHP mangles private property names in `var_export()` output. Declare reconstruction-relevant properties as `public readonly`.
:::

::: danger Casts holding secrets
`var_export()` dumps **every** property of the cast — including private ones — into the cache file. If your custom cast holds a secret (API key, encryption key, token), do **not** implement `__set_state()`: the class will simply be skipped by the file cache and keep working via the in-memory cache.
:::
