# Laravel

Works with Laravel 10–13. The core never touches the framework; the optional `HasLaravelIntegration` trait adds the request/model/response bridges.

## Setup

```php
// app/Providers/AppServiceProvider.php
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

public function boot(): void
{
    MetadataRegistry::setStoragePath(storage_path('framework/cache/data-objects'));
}
```

## Requests, models, responses

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;

class OrderData extends BaseData
{
    use HasLaravelIntegration;
    // ...
}

// Controller
public function store(Request $request): JsonResponse
{
    $order = OrderData::fromRequest($request); // FormRequest::validated() or #[Rules] validation

    return $order->toResponse($request);
}

// Anywhere
$order = OrderData::fromModel($orderModel);
$order = OrderData::from($orderModel);       // same thing — from() understands models
```

See [Laravel Integration](../features/laravel.md) for the full trait reference and [Validation](../features/validation.md) for `#[Rules]`.

## Deploy

```sh
vendor/bin/sdo-warm storage/framework/cache/data-objects app/Data
```

Add it to your deploy script (Envoyer/Forge/Vapor build step) right before `php-fpm` restarts. Clearing on rollback:

```sh
php artisan tinker --execute="StdOut\SimpleDataObjects\Support\MetadataRegistry::clearCache()"
```

## Octane / long-running workers

Nothing to configure: metadata and compiled closures live in per-worker static caches, so after the first request each worker runs entirely from memory. The validator factory is resolved from the container per call, so container rebinds between requests are picked up correctly.
