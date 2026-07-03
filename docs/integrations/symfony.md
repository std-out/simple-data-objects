# Symfony

The library has no Symfony-specific code — and needs none. Two touch points cover everything: a cache path at boot and `from()` at your controllers' edges.

## Setup

Point the metadata cache at the kernel cache directory:

```php
// src/Kernel.php
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        MetadataRegistry::setStoragePath($this->getCacheDir().'/data-objects');
    }
}
```

## Controllers

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/orders', methods: ['POST'])]
public function store(Request $request): JsonResponse
{
    // Symfony 6.3+: getPayload() merges JSON body and form data
    $order = OrderData::from($request->getPayload()->all());

    // or, for a raw JSON body — from() accepts JSON strings directly:
    $order = OrderData::from($request->getContent());

    return new JsonResponse($order); // BaseData is JsonSerializable
}
```

`#[Rules]` validation works out of the box (`illuminate/validation` runs standalone — no Laravel app involved): `OrderData::fromValidated(...)`. If you prefer `symfony/validator`, hydrate with `from()` and run your constraint validation on the typed object.

## Messenger

DTOs make ideal message payloads — immutable, self-validating, and `from(toArray())` roundtrips safely across serialization:

```php
$bus->dispatch(new ProcessOrder($order->toArray()));

// handler
$order = OrderData::from($message->payload);
```

## Deploy

```sh
vendor/bin/sdo-warm var/cache/prod/data-objects src/Data
```

Run it after `cache:warmup` in your deploy pipeline; combine with [opcache.preload](../features/cache.md#going-further-opcachepreload) for zero-cost first requests.
