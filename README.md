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
- **Enum support** — `BackedEnum` cast by value, `UnitEnum` passed through
- **Typed collections** — `#[DataCollection(UserData::class)]` produces a typed `TypedDataCollection`
- **Cast system** — `#[Cast(...)]` for dates, booleans, JSON, integers, floats, strings, enums with fallback, encryption
- **Key mapping** — `#[MapPropertyName]` per property, or `#[TransformKeys]` at class level
- **Hidden fields** — `#[Hidden]` excludes a property from `toArray()` / JSON output
- **Null omission** — `#[IgnoreIfNull]` skips a null field from serialization output
- **Reflection cache** — metadata built once per class, all derived sets computed at cache time
- **Laravel integration** — optional trait adds `fromRequest()`, `fromModel()`, `toResponse()`

---

## Requirements

| | Version |
|---|---|
| PHP | ^8.4 |
| `illuminate/contracts` | ^10.0 \| ^11.0 \| ^12.0 \| ^13.0 |
| `illuminate/support` | ^10.0 \| ^11.0 \| ^12.0 \| ^13.0 |

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

$team->members->count(); // 2
$team->members->first(); // UserData instance
```

Static factory:

```php
$collection = UserData::collection([
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob',   'email' => 'bob@example.com'],
]);
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

### Examples

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\BooleanCast;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;
use StdOut\SimpleDataObjects\Casts\DateTimeImmutableCast;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;
use StdOut\SimpleDataObjects\Casts\EnumCast;
use StdOut\SimpleDataObjects\Casts\FloatCast;
use StdOut\SimpleDataObjects\Casts\IntegerCast;
use StdOut\SimpleDataObjects\Casts\JsonCast;
use StdOut\SimpleDataObjects\Casts\TrimCast;

class ProductData extends BaseData
{
    public function __construct(
        #[Cast(new TrimCast(TrimCast::LOWERCASE))]
        public readonly string           $sku,

        #[Cast(new IntegerCast)]
        public readonly int              $quantity,

        #[Cast(new FloatCast(2))]
        public readonly float            $price,

        #[Cast(new BooleanCast)]
        public readonly bool             $available,

        #[Cast(new JsonCast)]
        public readonly array            $meta,

        #[Cast(new DateTimeCast('Y-m-d'))]
        public readonly DateTime         $createdAt,

        #[Cast(new DateTimeImmutableCast(DateTimeInterface::ATOM))]
        public readonly ?DateTimeImmutable $publishedAt = null,

        #[Cast(new EnumCast(Status::class, Status::Inactive))]
        public readonly Status           $status = Status::Inactive,
    ) {}
}
```

### Custom casts

Implement `CastsValue` to create your own cast:

```php
use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class MoneyCast implements CastsValue
{
    public function __construct(private readonly string $currency = 'USD') {}

    public function get(mixed $value): ?int
    {
        return $value === null ? null : (int) round((float) $value * 100);
    }

    public function set(mixed $value): ?string
    {
        return $value === null ? null : number_format($value / 100, 2);
    }
}

// Usage
#[Cast(new MoneyCast('EUR'))]
public readonly int $price,
```

---

## Laravel Integration

Add the `HasLaravelIntegration` trait to unlock `fromRequest()`, `fromModel()`, and `toResponse()`.

> **Requires:** `illuminate/http` and `illuminate/database` (available if using full Laravel).

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
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// In a controller
public function store(Request $request): JsonResponse
{
    $data = CreateUserData::fromRequest($request); // uses $request->validated() if available
    return $data->toResponse($request);            // JsonResponse
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
