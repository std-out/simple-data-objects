# Simple Data Objects

[![Tests](https://github.com/std-out/simple-data-objects/actions/workflows/tests.yml/badge.svg)](https://github.com/std-out/simple-data-objects/actions/workflows/tests.yml)
[![Security](https://github.com/std-out/simple-data-objects/actions/workflows/security.yml/badge.svg)](https://github.com/std-out/simple-data-objects/actions/workflows/security.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/std-out/simple-data-objects.svg)](https://packagist.org/packages/std-out/simple-data-objects)
[![Total Downloads](https://img.shields.io/packagist/dt/std-out/simple-data-objects.svg)](https://packagist.org/packages/std-out/simple-data-objects)
[![PHP](https://img.shields.io/badge/PHP-%5E8.4-777BB4?logo=php&logoColor=white)](https://packagist.org/packages/std-out/simple-data-objects)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Lightweight, attribute-driven Data Transfer Objects for PHP 8.4+. Works standalone or inside Laravel 10–13.

---

## Features

- **Hydrate from anything** — array, `stdClass`, `Arrayable`, `JsonSerializable`, JSON string
- **Nested DTOs** — deeply nested objects hydrated automatically
- **Flat embedding** — `#[Flatten]` inlines nested DTO fields into the parent `toArray()`
- **Enum support** — `BackedEnum` cast by value, `UnitEnum` passed through
- **Typed collections** — `#[DataCollection(UserData::class)]` produces a `TypedDataCollection<UserData>`
- **Cast system** — `#[Cast(...)]` for dates, booleans, JSON, integers, floats, strings, enums with fallback, encryption
- **Validation** — `#[Rules([...])]` with Laravel's full rule system; `validate()` and `fromValidated()`
- **Immutable copies** — `with(field: $value)` returns a new instance with overrides applied
- **Safe factory** — `tryFrom()` returns `null` instead of throwing on invalid input
- **Comparison** — `equals()` and `diff()` between two DTOs
- **Key mapping** — `#[MapPropertyName]` per property, or `#[TransformKeys]` at class level
- **Hidden fields** — `#[Hidden]` excludes a property from `toArray()` / JSON output
- **Null omission** — `#[IgnoreIfNull]` skips null fields from serialization output
- **Zero-reflection cache** — `MetadataRegistry::setStoragePath()` serializes metadata to PHP files; opcache picks them up automatically
- **Laravel integration** — optional trait adds `fromRequest()`, `fromModel()`, `toResponse()`

---

## Requirements

| | Version |
|---|---|
| PHP | ^8.4 |
| `illuminate/contracts` | ^10.0 \| ^11.0 \| ^12.0 \| ^13.0 |
| `illuminate/support` | ^10.0 \| ^11.0 \| ^12.0 \| ^13.0 |
| `illuminate/validation` | ^10.0 \| ^11.0 \| ^12.0 \| ^13.0 |

---

## Installation

```bash
composer require std-out/simple-data-objects
```

---

## Quick Start

```php
use StdOut\SimpleDataObjects\BaseData;

class UserData extends BaseData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}
}

$user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

$user->name;       // 'Alice'
$user->toArray();  // ['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => null]
$user->toJson();   // '{"name":"Alice","email":"alice@example.com","phone":null}'
```

---

## Hydration

### From array, stdClass, Arrayable, JsonSerializable

```php
UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
UserData::from((object) ['name' => 'Alice', 'email' => 'alice@example.com']);
UserData::from(collect(['name' => 'Alice', 'email' => 'alice@example.com']));
```

### From JSON string

```php
UserData::fromJson('{"name":"Alice","email":"alice@example.com"}');
```

### Safe factory — `tryFrom()`

Returns `null` instead of throwing when input is invalid or missing required fields:

```php
$user = UserData::tryFrom($request->all()); // ?UserData

if ($user === null) {
    // handle missing / invalid data
}
```

### Nested DTOs

```php
class ProfileData extends BaseData
{
    public function __construct(
        public readonly UserData $user,
        public readonly string $bio,
    ) {}
}

$profile = ProfileData::from([
    'user' => ['name' => 'Alice', 'email' => 'alice@example.com'],
    'bio'  => 'Software Engineer',
]);

$profile->user->name; // 'Alice'
```

### Enums

```php
enum Status: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}

class OrderData extends BaseData
{
    public function __construct(
        public readonly int    $id,
        public readonly Status $status,
    ) {}
}

$order = OrderData::from(['id' => 1, 'status' => 'active']);
$order->status; // Status::Active
```

### Collections

```php
use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\TypedDataCollection;

class TeamData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[DataCollection(UserData::class)]
        public readonly TypedDataCollection $members,
    ) {}
}

$team = TeamData::from([
    'name'    => 'Engineering',
    'members' => [
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob',   'email' => 'bob@example.com'],
    ],
]);

$team->members->first(); // UserData  — IDE knows the type
$team->members->last();  // UserData
```

Static factory from a DTO class:

```php
$collection = UserData::collection([...]);

// or using the generic factory directly
$collection = TypedDataCollection::of(UserData::class, [...]);
```

---

## Serialization

```php
$user->toArray();        // ['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => null]
$user->toJson();         // JSON string
(string) $user;          // same as toJson()
$user->only('name');     // ['name' => 'Alice']
$user->except('phone');  // ['name' => 'Alice', 'email' => 'alice@example.com']
json_encode($user);      // works via JsonSerializable
```

---

## Immutable Copies — `with()`

Creates a new instance with the specified fields overridden. The original is never mutated. Casts are applied to overridden values just like in `from()`.

```php
$original = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

$updated = $original->with(email: 'alice@new.com');

$original->email; // 'alice@example.com'
$updated->email;  // 'alice@new.com'

// Chain as needed
$result = $user
    ->with(name: 'Bob')
    ->with(email: 'bob@example.com');
```

---

## Comparison — `equals()` and `diff()`

```php
$a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
$b = $a->with(name: 'Bob');

$a->equals($b); // false

$a->diff($b);
// ['name' => ['Alice', 'Bob']]
// each entry is [this_value, other_value]
```

---

## Attributes

### `#[Hidden]` — exclude from output

```php
use StdOut\SimpleDataObjects\Attributes\Hidden;

class AuthData extends BaseData
{
    public function __construct(
        public readonly string $username,
        #[Hidden]
        public readonly string $password,
    ) {}
}

AuthData::from(['username' => 'alice', 'password' => 'secret'])->toArray();
// ['username' => 'alice']
```

### `#[IgnoreIfNull]` — omit field when null

```php
use StdOut\SimpleDataObjects\Attributes\IgnoreIfNull;

class ArticleData extends BaseData
{
    public function __construct(
        public readonly string  $title,
        #[IgnoreIfNull]
        public readonly ?string $subtitle = null,
    ) {}
}

ArticleData::from(['title' => 'Hello'])->toArray();
// ['title' => 'Hello']  — 'subtitle' omitted because null
```

### `#[Flatten]` — inline nested DTO fields

Embeds a nested DTO's fields directly into the parent's `toArray()` output (and reads them from the same flat input).

```php
use StdOut\SimpleDataObjects\Attributes\Flatten;

class AddressData extends BaseData
{
    public function __construct(
        public readonly string  $street,
        public readonly string  $city,
        public readonly ?string $zip = null,
    ) {}
}

class PersonData extends BaseData
{
    public function __construct(
        public readonly string  $name,
        #[Flatten]
        public readonly AddressData $address,
    ) {}
}

// Hydrate from a flat array
$person = PersonData::from([
    'name'   => 'Alice',
    'street' => '123 Main St',
    'city'   => 'Kyiv',
]);

$person->address->city; // 'Kyiv'

// Serializes back to the same flat structure
$person->toArray();
// ['name' => 'Alice', 'street' => '123 Main St', 'city' => 'Kyiv']
```

### `#[MapPropertyName]` — remap a single input key

```php
use StdOut\SimpleDataObjects\Attributes\MapPropertyName;

class UserData extends BaseData
{
    public function __construct(
        #[MapPropertyName('user_name')]
        public readonly string $userName,
    ) {}
}

UserData::from(['user_name' => 'alice']); // $userName = 'alice'
```

### `#[TransformKeys]` — remap all keys at class level

```php
use StdOut\SimpleDataObjects\Attributes\TransformKeys;

#[TransformKeys(TransformKeys::SNAKE_CASE)]
class UserData extends BaseData
{
    public function __construct(
        public readonly string $firstName,  // reads 'first_name'
        public readonly string $lastName,   // reads 'last_name'
    ) {}
}

UserData::from(['first_name' => 'Alice', 'last_name' => 'Smith']);
```

Available strategies: `TransformKeys::SNAKE_CASE`, `TransformKeys::CAMEL_CASE`.

> `#[MapPropertyName]` always takes priority over a class-level `#[TransformKeys]`.

---

## Validation

Apply `#[Rules([...])]` on any constructor parameter. Rules accept anything Laravel's Validator understands — strings, `Rule` objects, closures.

```php
use Illuminate\Validation\Rules\Password;
use StdOut\SimpleDataObjects\Attributes\Rules;

class RegisterData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string', 'max:100'])]
        public readonly string $name,

        #[Rules(['required', 'email:rfc,dns'])]
        public readonly string $email,

        #[Rules(['required', new Password->min(8)->letters()->numbers()])]
        public readonly string $password,

        #[Rules(['nullable', 'string', 'min:6'])]
        public readonly ?string $username = null,
    ) {}
}
```

### `validate()` — only validate, no hydration

```php
use Illuminate\Validation\ValidationException;

try {
    RegisterData::validate($request->all());
} catch (ValidationException $e) {
    $e->errors(); // ['email' => ['The email field must be a valid email address.']]
}
```

### `fromValidated()` — validate then hydrate

Throws `ValidationException` on failure; returns a hydrated instance on success.

```php
$data = RegisterData::fromValidated($request->all());
```

### `from()` — hydrate without validation

Use when input is already trusted (internal code, seeded data, test fixtures).

```php
$data = RegisterData::from($trustedArray);
```

> In Laravel, `fromRequest()` from `HasLaravelIntegration` calls `fromValidated()` automatically.

### Standalone usage

Without a Laravel container the library bootstraps a minimal validator internally — no extra setup needed.

```php
// works in any PHP project, no Laravel required
RegisterData::validate(['email' => 'bad']);
```

---

## Cast System

Apply any cast with `#[Cast(new SomeCast(...))]` on a constructor parameter.

### Built-in casts

| Cast | `get()` — hydration | `set()` — serialization |
|---|---|---|
| `DateTimeCast($format)` | string → `DateTime` | `DateTime` → formatted string |
| `DateTimeImmutableCast($format)` | string → `DateTimeImmutable` | `DateTimeImmutable` → formatted string |
| `EnumCast(Status::class, Status::Unknown)` | string → `Status` (falls back to default) | `Status` → value |
| `IntegerCast` | `"42"` → `42` | `42` → `42` |
| `FloatCast(2)` | `"9.9876"` → `9.99` | `9.99` → `9.99` |
| `BooleanCast` | `"yes"/"1"/"on"/"true"` → `true` | `bool` → `bool` |
| `TrimCast` | `" hello "` → `"hello"` | same |
| `TrimCast(TrimCast::LOWERCASE)` | `" ABC "` → `"abc"` | same |
| `TrimCast(TrimCast::UPPERCASE)` | `" abc "` → `"ABC"` | same |
| `JsonCast` | `'{"k":"v"}'` → `['k' => 'v']` | `['k' => 'v']` → `'{"k":"v"}'` |
| `EncryptedCast('key')` | base64 → plaintext | plaintext → AES-256-CBC + base64 |

### Example

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\{
    BooleanCast, DateTimeCast, DateTimeImmutableCast,
    EncryptedCast, EnumCast, FloatCast, IntegerCast, JsonCast, TrimCast,
};

class ProductData extends BaseData
{
    public function __construct(
        #[Cast(new TrimCast(TrimCast::LOWERCASE))]
        public readonly string             $sku,

        #[Cast(new IntegerCast)]
        public readonly int                $quantity,

        #[Cast(new FloatCast(2))]
        public readonly float              $price,

        #[Cast(new BooleanCast)]
        public readonly bool               $available,

        #[Cast(new JsonCast)]
        public readonly array              $meta,

        #[Cast(new DateTimeCast('Y-m-d'))]
        public readonly DateTime           $createdAt,

        #[Cast(new EnumCast(Status::class, Status::Inactive))]
        public readonly Status             $status = Status::Inactive,
    ) {}
}
```

### Custom casts

Implement `CastsValue` to create your own cast:

```php
use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class MoneyCast implements CastsValue
{
    public function get(mixed $value): ?int
    {
        return $value === null ? null : (int) round((float) $value * 100);
    }

    public function set(mixed $value): ?string
    {
        return $value === null ? null : number_format($value / 100, 2);
    }
}

#[Cast(new MoneyCast)]
public readonly int $priceInCents,
```

---

## Zero-Reflection Cache

By default metadata is built once per PHP process and kept in memory. For traditional PHP-FPM (new process per request) you can persist it to PHP files that opcache compiles and reuses:

```php
// bootstrap / AppServiceProvider
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

MetadataRegistry::setStoragePath(storage_path('framework/data-objects'));
```

The first time each DTO class is accessed, a cache file is written. On every subsequent request opcache serves the pre-compiled result — no reflection at all.

Clear the cache after deployment:

```php
MetadataRegistry::clearCache();
```

---

## Laravel Integration

Add the `HasLaravelIntegration` trait to unlock `fromRequest()`, `fromModel()`, and `toResponse()`.

> **Requires:** `illuminate/http` and `illuminate/database`.

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;

abstract class AppData extends BaseData
{
    use HasLaravelIntegration;
}
```

```php
class CreateUserData extends AppData
{
    public function __construct(
        #[Rules(['required', 'string'])]
        public readonly string $name,

        #[Rules(['required', 'email'])]
        public readonly string $email,
    ) {}
}

// In a controller
public function store(Request $request): JsonResponse
{
    // validates via #[Rules], hydrates, or throws ValidationException
    $data = CreateUserData::fromRequest($request);

    return $data->toResponse($request); // JsonResponse
}

// From an Eloquent model
$data = UserData::fromModel($user);
```

---

## Running Tests

```bash
make test        # run PHPUnit in Docker
make lint        # fix code style with Pint
make lint-check  # check style without changes (CI)
make shell       # open shell in container
```

---

## License

MIT — see [LICENSE](LICENSE).
