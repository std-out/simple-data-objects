# Laravel Integration

The `HasLaravelIntegration` trait adds convenience methods for working inside a Laravel application. It is **optional** — the core library works without it.

For assigning a data object directly as an Eloquent attribute cast (`protected function casts(): array { return ['address' => AddressData::class]; }`), see [Eloquent Attribute Casting](./eloquent-casting.md) — a separate, equally optional trait.

`HasLaravelIntegration` also unlocks [automatic controller injection](./service-provider.md#controller-injection): type-hint a DTO as a controller parameter and get it hydrated + validated with no `FormRequest` at all.

## Setup

Create a base class for your application DTOs:

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;

abstract class AppData extends BaseData
{
    use HasLaravelIntegration;
}
```

Now all your DTOs that extend `AppData` get `fromRequest()`, `fromModel()`, and `toResponse()`.

## fromRequest()

Hydrates a DTO from an HTTP request. Uses `$request->validated()` if available (e.g., a `FormRequest`), otherwise falls back to `$request->all()`. **Validation via `#[Rules]` runs automatically.**

```php
class CreateUserData extends AppData
{
    public function __construct(
        #[Rules(['required', 'string', 'max:100'])]
        public readonly string $name,

        #[Rules(['required', 'email'])]
        public readonly string $email,
    ) {}
}

// In a controller:
public function store(Request $request): JsonResponse
{
    $data = CreateUserData::fromRequest($request);
    // ↑ validates #[Rules], throws ValidationException on failure
    // ↑ hydrates and returns CreateUserData

    $user = User::create($data->toArray());
    return response()->json($user, 201);
}
```

## fromModel()

Hydrates a DTO from an Eloquent model's own attributes (`$model->attributesToArray()` — no relations):

```php
$data = UserData::fromModel(User::findOrFail($id));
$data->name;  // model attribute
```

To include a relation, mark the property with [`#[WhenLoaded]`](../attributes/when-loaded.md) — it's added only if the relation was actually eager-loaded:

```php
class OrderData extends AppData
{
    public function __construct(
        public readonly int $id,
        #[WhenLoaded('customer')]
        public readonly ?CustomerData $customer = null,
    ) {}
}

OrderData::fromModel(Order::find($id));                    // customer: null
OrderData::fromModel(Order::with('customer')->find($id));  // customer: hydrated
```

::: tip
Combine `fromModel()` with `with()` for clean "read-modify-return" patterns:

```php
return UserData::fromModel($user)->with(email: $newEmail)->toJson();
```
:::

## toResponse()

Returns a `JsonResponse` from the DTO's array representation, with optional `status` and `headers`:

```php
public function show(int $id): JsonResponse
{
    $user = User::findOrFail($id);
    return UserData::fromModel($user)->toResponse($request, status: 200);
}
```

Add [`#[WrapIn('data')]`](../attributes/wrap-in.md) on the class to wrap the payload under a key — `toArray()`/`toJson()` stay unwrapped, only `toResponse()` is affected.

## paginatedCollection()

Wraps a Laravel paginator into a `{"data":[...],"meta":{...},"links":{...}}` envelope — see [Pagination & Response Envelope](./pagination.md).

```php
return ItemData::paginatedCollection(Item::paginate(20));
```

## Validation + FormRequest

If you are already using a `FormRequest`, its `validated()` data is used by `fromRequest()`. `#[Rules]` in the DTO run **on top** of the FormRequest rules:

```php
class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return ['name' => 'required'];
    }
}

// In controller:
$data = UserData::fromRequest($request); // uses $request->validated() + #[Rules]
```

## Cache Warming on Deploy

The compiled hydrator/serializer for each DTO is built lazily, on its first
use. Without pre-warming, that means whichever request happens to hit a
given DTO class first pays the compile cost — fine in dev, wasteful right
after a deploy when every worker starts cold at once.

### 1. Register the cache path

Set `cache_path` in `config/simple-data-objects.php` (see [Service Provider &
Commands](./service-provider.md#config)) and
`SimpleDataObjectsServiceProvider` calls `MetadataRegistry::setStoragePath()`
for you — no `AppServiceProvider` wiring needed:

```php
// config/simple-data-objects.php
return [
    'cache_path' => env('SDO_CACHE_PATH', storage_path('framework/cache/data-objects')),
];
```

The provider applies whatever `cache_path` resolves to, in every environment — including your test suite, if `cache_path` is set there too. Leave `SDO_CACHE_PATH` unset in `.env.testing` (or otherwise scope the value to non-testing environments) if you don't want generated cache files during test runs.

### 2. Warm it as a deploy step

```sh
php artisan sdo:warm
```

This is also registered against `php artisan optimize` / `optimize:clear`
automatically, so it needs no extra deploy-script wiring — just make sure it
runs right before workers restart, so every worker starts from an
already-compiled cache instead of building it on the first request it
happens to serve. See [Metadata
Cache](../features/cache.md#pre-warming-on-deploy) for what the command actually
scans and writes, and [opcache.preload](../features/cache.md#going-further-opcachepreload)
to skip the file-read cost too.

Not using Laravel? The standalone `bin/sdo-warm` Composer binary does the
same thing with no framework dependency at all:

```sh
vendor/bin/sdo-warm storage/framework/cache/data-objects app/Data
```

### 3. Clear it on rollback

```sh
php artisan sdo:clear
```

Run this whenever DTO classes or their attributes change between deploys —
a stale cache entry keeps serving the old compiled shape otherwise.

## Octane / Long-Running Workers

Nothing to configure: metadata and compiled closures live in per-worker
static caches, so after the first request each worker runs entirely from
memory. The validator factory is resolved from the container per call, so
container rebinds between requests are picked up correctly.
