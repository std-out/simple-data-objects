# Introduction

**Simple Data Objects** is a lightweight PHP 8.4+ library for creating typed Data Transfer Objects (DTOs) using constructor property promotion and PHP attributes.

## Why Simple Data Objects?

DTOs are ubiquitous in modern PHP applications — they carry data between layers, validate input from HTTP requests, and serialize to/from APIs. Yet most implementations involve a lot of repetitive plumbing code.

Simple Data Objects removes that plumbing:

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;

class CreateOrderData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string', 'max:200'])]
        public readonly string $title,

        #[Rules(['required', 'email'])]
        public readonly string $customerEmail,

        #[Cast(new DateTimeCast('Y-m-d'))]
        public readonly DateTime $deliveryDate,

        public readonly ?string $notes = null,
    ) {}
}

// Validate + hydrate in one step
$order = CreateOrderData::fromValidated($request->all());

$order->title;           // 'New laptop'
$order->deliveryDate;    // DateTime object
$order->toArray();       // ['title' => ..., 'customerEmail' => ..., 'deliveryDate' => '2025-01-15', ...]
$order->toJson();        // JSON string
```

## Core Concepts

### Hydration
`from()` accepts arrays, `stdClass`, `Arrayable`, `JsonSerializable`, and JSON strings. Nested DTOs, enums, and collections are resolved automatically.

### Serialization
`toArray()` and `toJson()` serialize the object back to its wire format. Casts apply in both directions — hydration reads them, serialization writes them.

### Attributes
All behaviour is declared on the constructor parameters:
- **`#[Cast(...)]`** — type conversion for dates, booleans, JSON, encryption, …
- **`#[Rules([...])]`** — Laravel validation rules
- **`#[Hidden]`** — exclude from `toArray()` output
- **`#[IgnoreIfNull]`** — omit null fields from output
- **`#[Flatten]`** — inline nested DTO fields into the parent array
- **`#[MapPropertyName]`** / **`#[TransformKeys]`** — key remapping

### Performance
Reflection runs **once per class per process**, results are cached in memory. Enable file-based cache with `MetadataRegistry::setStoragePath()` to have opcache pre-compile all metadata across requests.

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.4 |
| `illuminate/contracts` | ^10 \| ^11 \| ^12 \| ^13 |
| `illuminate/support` | ^10 \| ^11 \| ^12 \| ^13 |
| `illuminate/validation` | ^10 \| ^11 \| ^12 \| ^13 |

Works standalone — no Laravel application required. Validation uses the library's own minimal validator bootstrap when no container is available.
