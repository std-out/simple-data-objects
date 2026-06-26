# Simple Data Objects

[![Tests](https://github.com/std-out/simple-data-objects/actions/workflows/tests.yml/badge.svg)](https://github.com/std-out/simple-data-objects/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/std-out/simple-data-objects.svg)](https://packagist.org/packages/std-out/simple-data-objects)
[![Total Downloads](https://img.shields.io/packagist/dt/std-out/simple-data-objects.svg)](https://packagist.org/packages/std-out/simple-data-objects)
[![PHP Version](https://img.shields.io/packagist/php-v/std-out/simple-data-objects.svg)](https://packagist.org/packages/std-out/simple-data-objects)
[![License](https://img.shields.io/packagist/l/std-out/simple-data-objects.svg)](LICENSE)

Lightweight, zero-magic Data Transfer Objects for PHP 8.1+ with attribute-driven hydration. Works standalone or inside Laravel 10–13.

---

## Features

- **Hydrate from anything** — array, `stdClass`, `Arrayable`, `JsonSerializable`, JSON string
- **Nested DTOs** — deeply nested objects hydrated automatically
- **Enum support** — `BackedEnum` cast by value, `UnitEnum` passed through
- **Typed collections** — `#[DataCollection(SomeData::class)]` on a parameter creates a `TypedDataCollection`
- **Key mapping** — `#[MapPropertyName('snake_key')]` or class-level `#[TransformKeys]`
- **Hidden fields** — `#[Hidden]` excludes properties from `toArray()` / JSON output
- **Laravel integration** — optional trait adds `fromRequest()`, `fromModel()`, `toResponse()`
- **Reflection cache** — metadata is built once per class and reused

---

## Requirements

| Dependency | Version |
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

$user->name;        // 'Alice'
$user->toArray();   // ['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => null]
$user->toJson();    // '{"name":"Alice","email":"alice@example.com","phone":null}'
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
        public readonly int $id,
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
$user->toArray();           // ['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => null]
$user->toJson();            // JSON string
(string) $user;             // same as toJson()
$user->only('name');        // ['name' => 'Alice']
$user->except('phone');     // ['name' => 'Alice', 'email' => 'alice@example.com']
json_encode($user);         // works via JsonSerializable
```

### Exclude null fields

```php
class ArticleData extends BaseData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $summary = null,
    ) {}

    protected function ignoreIfNull(): array
    {
        return ['summary'];
    }
}

ArticleData::from(['title' => 'Hello'])->toArray();
// ['title' => 'Hello']  — 'summary' omitted because it's null
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

### `#[MapPropertyName]` — remap a single key

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
        public readonly string $firstName,  // reads 'first_name' from input
        public readonly string $lastName,   // reads 'last_name' from input
    ) {}
}

UserData::from(['first_name' => 'Alice', 'last_name' => 'Smith']);
```

Available strategies: `TransformKeys::SNAKE_CASE`, `TransformKeys::CAMEL_CASE`.

> Per-property `#[MapPropertyName]` always takes priority over the class-level strategy.

---

## Laravel Integration

Add the `HasLaravelIntegration` trait to unlock `fromRequest()`, `fromModel()`, and `toResponse()`.

> **Requires:** `illuminate/http` and `illuminate/database` (already present if using full Laravel).

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;

abstract class AppData extends BaseData
{
    use HasLaravelIntegration;
}
```

Then in your application:

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
    return $data->toResponse($request);            // returns JsonResponse
}

// From an Eloquent model
$data = UserData::fromModel($user);
```

---

## Running Tests

```bash
# With Docker (recommended)
make test

# Or directly
composer test
```

---

## License

MIT — see [LICENSE](LICENSE).
