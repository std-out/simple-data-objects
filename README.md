# Simple Data Objects

[![Tests](https://github.com/std-out/simple-data-objects/actions/workflows/tests.yml/badge.svg)](https://github.com/std-out/simple-data-objects/actions/workflows/tests.yml)
[![Security](https://github.com/std-out/simple-data-objects/actions/workflows/security.yml/badge.svg)](https://github.com/std-out/simple-data-objects/actions/workflows/security.yml)
[![Coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/yuriizee/1a6bdbea77d160eeaf4524b8e165d3ac/raw/coverage.json)](https://github.com/std-out/simple-data-objects/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/std-out/simple-data-objects.svg)](https://packagist.org/packages/std-out/simple-data-objects)
[![Total Downloads](https://img.shields.io/packagist/dt/std-out/simple-data-objects.svg)](https://packagist.org/packages/std-out/simple-data-objects)
[![PHP](https://img.shields.io/badge/PHP-%5E8.4-777BB4?logo=php&logoColor=white)](https://packagist.org/packages/std-out/simple-data-objects)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**Lightweight, attribute-driven DTOs for PHP 8.4+.**  
Works standalone or inside Laravel 10–13. No reflection in production.

```bash
composer require std-out/simple-data-objects
```

→ **[Full documentation](https://std-out.github.io/simple-data-objects/)**

---

## Why

| | Simple Data Objects |
|---|---|
| Reflection | Once per class, then zero — file cache compiled by opcache |
| Boilerplate | None — constructor props + attributes |
| Roundtrip | `from(toArray())` always works, mapped keys included |
| Standalone | Validation works without a Laravel app |
| Pipelines | Middleware-style input preprocessing, class or property level |

---

## Quick Look

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Attributes\{Cast, Rules, Pipe};
use StdOut\SimpleDataObjects\Casts\DateTimeCast;
use StdOut\SimpleDataObjects\Pipes\TrimValuePipe;

class CreateOrderData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string', 'max:200'])]
        #[Pipe(TrimValuePipe::class)]
        public readonly string $title,

        #[Rules(['required', 'email'])]
        public readonly string $customerEmail,

        #[Cast(new DateTimeCast('Y-m-d'))]
        public readonly \DateTime $deliveryDate,

        public readonly ?string $notes = null,
    ) {}
}

// validate → pipe → cast → hydrate
$order = CreateOrderData::fromValidated($request->all());

$order->title;            // trimmed string
$order->deliveryDate;     // \DateTime object
$order->toArray();        // ['title' => ..., 'customerEmail' => ..., 'deliveryDate' => '2025-01-15']
$order->toJson();         // JSON string
$order->with(notes: 'x'); // immutable copy with override
```

---

## Killer Features

### DataPipe — input preprocessing middleware

Transform input before hydration, at class or property level:

```php
use StdOut\SimpleDataObjects\Pipes\{TrimStringsPipe, NullifyEmptyStringsPipe};
use StdOut\SimpleDataObjects\Pipes\{TrimValuePipe, NullifyEmptyStringValuePipe};

// Class-level: runs on the entire input array
#[Pipe(TrimStringsPipe::class, NullifyEmptyStringsPipe::class)]
class ContactData extends BaseData { ... }

// Property-level: runs only on that field's value
class ProfileData extends BaseData
{
    public function __construct(
        #[Pipe(TrimValuePipe::class)]
        public readonly string $name,

        #[Pipe(TrimValuePipe::class, NullifyEmptyStringValuePipe::class)]
        public readonly ?string $bio = null,
    ) {}
}
```

Custom pipe in 3 lines:

```php
final class UpperCasePipe implements ValuePipe
{
    public function handle(mixed $value, string $paramName, callable $next): mixed
    {
        return $next(is_string($value) ? strtoupper($value) : $value);
    }
}
```

### Zero-reflection in production

```php
// bootstrap / AppServiceProvider — run once
MetadataRegistry::setStoragePath(storage_path('framework/data-objects'));
```

First access writes a PHP file per class. Every subsequent request opcache serves it — **zero reflection**.

### Immutable copies with `with()`

```php
$updated = $user->with(email: 'new@example.com'); // original unchanged
$updated->equals($user);                           // false
$user->diff($updated);                             // ['email' => ['old@...', 'new@...']]
```

### Typed collections with IDE generics

```php
#[DataCollection(UserData::class)]
public readonly TypedDataCollection $members,

// IDE infers type throughout the chain:
$team->members->filter(fn (UserData $u) => $u->active)->first()->name;
```

### Validation anywhere

```php
// In Laravel — fromRequest() auto-validates
$data = CreateOrderData::fromRequest($request);

// Standalone — no Laravel app needed
CreateOrderData::validate($rawArray); // throws ValidationException
```

---

## All Attributes

| Attribute | Where | Effect |
|---|---|---|
| `#[Cast(new DateTimeCast('Y-m-d'))]` | property | type conversion on hydration + serialization |
| `#[Rules(['required', 'email'])]` | property | Laravel validation rules |
| `#[Pipe(TrimValuePipe::class)]` | property | value-level preprocessing pipeline |
| `#[Pipe(TrimStringsPipe::class)]` | class | array-level preprocessing pipeline |
| `#[Flatten]` | property | inline nested DTO fields into parent |
| `#[Hidden]` | property | exclude from `toArray()` / JSON |
| `#[IgnoreIfNull]` | property | omit from output when `null` |
| `#[MapPropertyName('input_key')]` | property | map different input key → property |
| `#[TransformKeys(TransformKeys::SNAKE_CASE)]` | class | transform all keys at class level |
| `#[DataCollection(ItemData::class)]` | property | typed collection of DTOs |

## Built-in Casts

`DateTimeCast` · `DateTimeImmutableCast` · `EnumCast` · `BooleanCast` · `IntegerCast` · `FloatCast` · `TrimCast` · `JsonCast` · `EncryptedCast` (XSalsa20-Poly1305)

---

## License

MIT — see [LICENSE](LICENSE).
