# Laravel Integration

The `HasLaravelIntegration` trait adds three convenience methods for working inside a Laravel application. It is **optional** — the core library works without it.

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

Hydrates a DTO from an Eloquent model via `$model->toArray()`:

```php
$data = UserData::fromModel(User::findOrFail($id));
$data->name;  // model attribute
```

::: tip
Combine `fromModel()` with `with()` for clean "read-modify-return" patterns:

```php
return UserData::fromModel($user)->with(email: $newEmail)->toJson();
```
:::

## toResponse()

Returns a `JsonResponse` from the DTO's array representation:

```php
public function show(int $id): JsonResponse
{
    $user = User::findOrFail($id);
    return UserData::fromModel($user)->toResponse($request);
}
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

Point the metadata cache at a storage directory in `AppServiceProvider`.
Skip it in tests so generated cache files don't leak into a test run:

```php
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

public function boot(): void
{
    if (! app()->runningUnitTests()) {
        MetadataRegistry::setStoragePath(
            storage_path('framework/cache/data-objects')
        );
    }
}
```

### 2. Warm it as a deploy step

`sdo-warm` is a plain Composer binary, not an artisan command — it has no
framework dependency, so it runs as its own step in your deploy pipeline
rather than through `php artisan optimize`:

```sh
vendor/bin/sdo-warm storage/framework/cache/data-objects app/Data
```

Add it wherever your other build-time steps live — a Forge/Envoyer deploy
script, a Vapor build hook, or a CI job — right before workers restart, so
every worker starts from an already-compiled cache instead of building it
on the first request it happens to serve. See [Metadata
Cache](./cache.md#pre-warming-on-deploy) for what the command actually
scans and writes, and [opcache.preload](./cache.md#going-further-opcachepreload)
to skip the file-read cost too.

### 3. Clear it on rollback

```sh
php artisan tinker --execute="StdOut\SimpleDataObjects\Support\MetadataRegistry::clearCache()"
```

Run this whenever DTO classes or their attributes change between deploys —
a stale cache entry keeps serving the old compiled shape otherwise.
