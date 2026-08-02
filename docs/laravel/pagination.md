# Pagination & Response Envelope

`PaginatedDataCollection` wraps a Laravel paginator, hydrating each item through the same compiled hydrator as `from()`, and delegates `meta`/`links` to the paginator itself. It's **optional** — available only through `HasLaravelIntegration`, and the core package has no dependency on `illuminate/pagination` or `illuminate/http` unless you use it.

## Usage

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;

class ItemData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        public readonly string $name,
        public readonly float $price,
    ) {}
}
```

```php
public function index()
{
    $page = ItemData::paginatedCollection(Item::paginate(20));

    return $page; // Responsable — {"data":[...],"meta":{...},"links":{...}}
}
```

## Shape

```json
{
    "data": [{ "name": "Widget", "price": 9.99 }],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 20,
        "total": 45,
        "from": 1,
        "to": 20
    },
    "links": {
        "first": "https://api.test/items?page=1",
        "last": "https://api.test/items?page=3",
        "prev": null,
        "next": "https://api.test/items?page=2"
    }
}
```

## API

- `PaginatedDataCollection::of(ItemData::class, $paginator)` — the underlying static factory, usable directly without `HasLaravelIntegration`.
- `$page->data` — the hydrated `TypedDataCollection<ItemData>`.
- `$page->toArray()` / `$page->jsonSerialize()` — the shape above.
- `$page->toResponse($request, status: 200, headers: [])` — `Illuminate\Http\JsonResponse`.

## #[WrapIn] — single-object envelope

For a single (non-paginated) `toResponse()`, wrap the payload under a key without touching `toArray()` — `from(toArray())` round-trips unaffected:

```php
use StdOut\SimpleDataObjects\Attributes\WrapIn;

#[WrapIn('data')]
class OrderData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        public readonly string $title,
    ) {}
}

$order->toArray();               // ['title' => 'Widget']
$order->toResponse($request);    // {"data": {"title": "Widget"}}
```
