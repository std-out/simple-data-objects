# #[WrapIn]

Wraps a single object's `toResponse()` payload under a key — the equivalent of a Laravel API Resource's default `{"data": {...}}` envelope, without needing a Resource class.

Only affects `toResponse()` (via [`HasLaravelIntegration`](../laravel/pagination.md)). `toArray()`, `toJson()`, and `from()` are untouched, so `from($dto->toArray())` still round-trips.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\WrapIn;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;

#[WrapIn('data')]
class OrderData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        public readonly string $title,
    ) {}
}
```

```php
$order = OrderData::from(['title' => 'Widget']);

$order->toArray();             // ['title' => 'Widget']
$order->toResponse($request);  // JsonResponse: {"data": {"title": "Widget"}}
```

## status and headers

```php
$order->toResponse($request, status: 201, headers: ['X-Custom' => 'value']);
```
