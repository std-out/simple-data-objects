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

## Metadata Cache in Laravel

Add this to your `AppServiceProvider`:

```php
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

public function boot(): void
{
    if (! app()->runningUnitTests()) {
        MetadataRegistry::setStoragePath(
            storage_path('framework/data-objects')
        );
    }
}
```

Clear on deploy (e.g., in `Artisan` post-deploy hook):

```php
MetadataRegistry::clearCache();
```
