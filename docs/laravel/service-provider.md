# Service Provider & Commands

`SimpleDataObjectsServiceProvider` adds three artisan commands, publishable config, and one piece of container wiring: type-hinting a `BaseData` subclass as a controller (or route closure) parameter auto-hydrates it from the current request, no `FormRequest` needed.

It is **not auto-discovered** — this package has no `extra.laravel.providers` entry in `composer.json`, on purpose. Everything else in this package (`HasLaravelIntegration`, `IsEloquentCastable`, `WireableData`, casts) is opt-in per-class already; the provider is the one piece that adds process-wide container behavior (the controller-injection hook below applies to every `BaseData` subclass in the app, not just one you've annotated), so registering it is a deliberate, visible step rather than something that silently turns on for every Laravel app that happens to install this package — including ones that only want the standalone DTO features and never touch the Laravel-specific pieces at all.

## Installation

Register the provider yourself:

```php
// bootstrap/providers.php (Laravel 11+)
return [
    // ...
    StdOut\SimpleDataObjects\Laravel\SimpleDataObjectsServiceProvider::class,
];
```

```php
// config/app.php 'providers' array (Laravel 10)
'providers' => [
    // ...
    StdOut\SimpleDataObjects\Laravel\SimpleDataObjectsServiceProvider::class,
],
```

A full Laravel application already provides `illuminate/console` and `illuminate/database` via `laravel/framework`, so there's nothing extra to install — this step only matters if you're assembling a bare set of Illuminate components without the full framework.

## Config

Publish `config/simple-data-objects.php` to tweak the defaults:

```sh
php artisan vendor:publish --tag=simple-data-objects-config
```

```php
return [
    'cache_path' => null,               // null = in-memory-only cache
    'paths' => [app_path('Data')],       // scanned by sdo:warm; default make:data output dir
    'inject_from_request' => true,       // controller injection, see below
];
```

Setting `cache_path` also makes the provider call `MetadataRegistry::setStoragePath()` for you in `boot()` — you no longer need to do this by hand in your own `AppServiceProvider`.

## Controller Injection

Type-hint a `BaseData` subclass that uses [`HasLaravelIntegration`](./index.md) as a controller-method or route-closure parameter:

```php
class CreateOrderData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        #[Rules(['required', 'string'])]
        public readonly string $customerName,

        #[Rules(['required', 'numeric'])]
        public readonly float $total,
    ) {}
}

// No FormRequest needed:
public function store(CreateOrderData $data)
{
    // $data is already validated (#[Rules]), piped, and cast
}
```

Under the hood, the provider registers a `beforeResolving(BaseData::class, ...)` container hook — scoped to `BaseData` and its subclasses only, so it adds no overhead to unrelated container resolutions. When the container is about to resolve a concrete (non-abstract) `BaseData` subclass that:

- isn't already explicitly bound,
- uses `HasLaravelIntegration` (has a `fromRequest()` method), and
- has a request available in the container,

...it binds `$class::fromRequest($request)` just-in-time. A validation failure throws the normal `ValidationException`, which Laravel's default exception handler turns into a `422` — exactly as if you'd called `fromRequest()` yourself.

A `BaseData` subclass that does **not** use `HasLaravelIntegration` is left alone — the container falls through to its normal (usually unresolvable, since these classes take scalar constructor arguments) autowiring attempt.

::: tip
Disable this globally with `'inject_from_request' => false` in config, or bind the class yourself beforehand (e.g. in a test) — an explicit binding is never overridden.
:::

::: warning
Laravel's console kernel binds a synthetic empty `Request` for artisan commands too (`SetRequestForConsole`), so this hook isn't a reliable way to detect "am I in an HTTP request" — it only guards the bare-container case (no kernel booted at all, e.g. some unit tests). Avoid type-hinting an injectable `BaseData` subclass as a queued job's constructor argument expecting it to hydrate from a real request.
:::

## Artisan Commands

### `sdo:warm`

Wraps [`CacheWarmer`](../features/cache.md) — the same discovery/persistence logic as the standalone `bin/sdo-warm` binary, wired to config instead of CLI arguments:

```sh
php artisan sdo:warm                      # uses config('simple-data-objects.paths' / 'cache_path')
php artisan sdo:warm app/Data --cache=storage/framework/cache/data-objects
```

On Laravel 11+, this is also registered against `php artisan optimize` / `optimize:clear` automatically — no extra wiring needed in a deploy script. On 10.x, run it as its own deploy step (see [Cache Warming on Deploy](./index.md#cache-warming-on-deploy)).

### `sdo:clear`

Wraps `MetadataRegistry::clearCache()` — run whenever DTO classes or their attributes change between deploys, since a stale `.meta.php` cache entry keeps serving the old compiled shape otherwise:

```sh
php artisan sdo:clear
```

### `make:data`

Generates a DTO stub:

```sh
php artisan make:data OrderData
```

```php
<?php

declare(strict_types=1);

namespace App\Data;

use StdOut\SimpleDataObjects\BaseData;

class OrderData extends BaseData
{
    public function __construct(
    ) {}
}
```

With `--from-model`, it reads the model's table columns (`Schema::getColumns()`, database-agnostic, Laravel 11+) and generates typed, constructor-promoted properties instead — the auto-increment primary key and any generated/computed columns are skipped, since neither is client-supplied input:

```sh
php artisan make:data OrderData --from-model=Order --rules
```

```php
#[TransformKeys(TransformKeys::SNAKE_CASE)]
class OrderData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string'])]
        public readonly string $customerName,

        #[Rules(['nullable', 'numeric'])]
        public readonly ?float $total = null,
    ) {}
}
```

`#[TransformKeys(TransformKeys::SNAKE_CASE)]` is added automatically whenever `--from-model` is used — columns are `snake_case`, generated properties are camelCased, so the class needs the naming strategy to hydrate correctly from `fromModel()` or a raw DB row.

Column-type mapping is best-effort (a stub generator, not a full ORM introspection engine): unmatched column types fall back to `mixed` with no rule rather than guessing wrong.

| `type_name` | PHP type | `#[Rules]` (with `--rules`) |
|---|---|---|
| `varchar`, `char`, `text`, `string` | `string` | `string` |
| `int`, `integer`, `bigint`, `smallint`, `tinyint` | `int` | `integer` |
| `bool`, `boolean` | `bool` | `boolean` |
| `float`, `double`, `decimal`, `numeric` | `float` | `numeric` |
| `date`, `datetime`, `timestamp` | `string` | `date` |
| `json`, `jsonb` | `array` | `array` |
| anything else | `mixed` | *(none)* |

Options:

- `--from-model=<Model>` — generate typed properties from a model's table columns.
- `--rules` — add `#[Rules]` attributes inferred from column types (has no effect without `--from-model`).
- `--collection` — add a doc-comment pointing at `static::collection()`, the existing way to hydrate a list of this DTO — no second `{Name}Collection` class is generated, since `BaseData::collection()` already covers it.
- `--path=<dir>` — output directory (defaults to `config('simple-data-objects.paths')[0]`); must live under `app_path()` since the namespace is derived from the directory's location relative to it.
- `--force` — overwrite an existing file.

`--from-model` accepts either a bare model name (resolved the same way `make:model`-family commands do — under `App\Models\` if that directory exists, otherwise directly under the app's root namespace) or an already fully-qualified class name.
