# Simple Data Objects

[![Tests](https://github.com/std-out/simple-data-objects/actions/workflows/tests.yml/badge.svg)](https://github.com/std-out/simple-data-objects/actions/workflows/tests.yml)
[![Security](https://github.com/std-out/simple-data-objects/actions/workflows/security.yml/badge.svg)](https://github.com/std-out/simple-data-objects/actions/workflows/security.yml)
[![Coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/yuriizee/1a6bdbea77d160eeaf4524b8e165d3ac/raw/coverage.json)](https://github.com/std-out/simple-data-objects/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/std-out/simple-data-objects.svg)](https://packagist.org/packages/std-out/simple-data-objects)
[![Total Downloads](https://img.shields.io/packagist/dt/std-out/simple-data-objects.svg)](https://packagist.org/packages/std-out/simple-data-objects)
[![PHP](https://img.shields.io/badge/PHP-%5E8.4-777BB4?logo=php&logoColor=white)](https://packagist.org/packages/std-out/simple-data-objects)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**Lightweight, attribute-driven DTOs for PHP 8.4+ — compiled hydration and serialization, zero reflection at runtime.**  
Built for bulk imports that need to stay memory-flat, Octane/Swoole/FrankenPHP APIs where every allocation costs RPS, and standalone PHP projects that don't want a Laravel app just for validation. Works standalone or inside Laravel 12–13.

```bash
composer require std-out/simple-data-objects
```

→ **[Full documentation](https://std-out.github.io/simple-data-objects/)**

---

## Why

| | Simple Data Objects |
|---|---|
| Bulk import / ETL | `lazyCollection()` keeps memory flat regardless of row count |
| Octane / Swoole / FrankenPHP | Compiled per-class closures — zero reflection, zero dispatch overhead per request |
| Non-Laravel projects | Validation and casting work without a Laravel app |
| Boilerplate | None — constructor props + attributes |
| Roundtrip | `from(toArray())` always works, mapped keys included |
| Pipelines | Middleware-style input preprocessing, class or property level |

### Performance

Benchmarked against **[spatie/laravel-data](https://github.com/spatie/laravel-data)** — the most widely used full-featured data-object library in the PHP/Laravel ecosystem, and one we have a lot of respect for; it's more feature-rich than this library in several areas (lazy properties, wrappers, partial data, transformers). Identical DTO shapes, 20,000 iterations per scenario, PHP 8.4, inside a fully booted Laravel app (not a synthetic standalone script). Medians of 5 runs:

| Scenario | Simple Data Objects | spatie/laravel-data | Advantage |
|---|---|---|---|
| Hydration — flat DTO | ~6,550,000 ops/s | ~132,000 ops/s | **~50× faster** |
| Hydration — nested DTO | ~3,390,000 ops/s | ~95,000 ops/s | **~36× faster** |
| Hydration — collection of 20 | ~209,000 ops/s | ~10,200 ops/s | **~21× faster** |
| Serialization — flat DTO | ~14,900,000 ops/s | ~249,000 ops/s | **~60× faster** |
| Serialization — nested DTO | ~7,500,000 ops/s | ~166,000 ops/s | **~47× faster** |
| Streaming — 100k-row CSV import | ~67,200 rows/s | ~35,800 rows/s | **~87% faster**, same flat ~12 KB memory footprint |

Absolute numbers vary with hardware; the ratios stay stable across runs. CPU time per operation follows the same ratios — less CPU burned per request means more headroom per server. Streaming a large import with `lazyCollection()` keeps memory flat regardless of row count — the win there is architectural (no full materialization), not a per-row memory difference from spatie/laravel-data, which also streams comparably once both sides are measured on equal footing.

Don't take the numbers on faith — **[run the benchmarks yourself](https://github.com/std-out/simple-data-objects-benchmark)**: clone the companion repo, `make bench`, or swap in your own payload shapes.

**Where spatie/laravel-data is the better fit:** it has years more production mileage, a larger community, and a broader feature set — lazy properties, wrappers, partial data, transformers, and deep integration with the rest of the Spatie ecosystem. This library trades some of that breadth for a narrower, compiled hot path. If those features matter more to you than raw hydration/serialization speed, spatie/laravel-data is the right choice.

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

`from()` and `toArray()` compile a specialized closure per class — plain properties become direct array reads. Enable the file cache and the compiled code persists between requests:

```php
// bootstrap / AppServiceProvider — run once
MetadataRegistry::setStoragePath(storage_path('framework/data-objects'));
```

Pre-warm it on deploy so even the first request is hot:

```bash
vendor/bin/sdo-warm storage/framework/data-objects app/Data
```

Every worker then starts with opcache-compiled metadata **and** hydration/serialization code — zero reflection, zero compilation at runtime.

### Streaming large datasets

`lazyCollection()` hydrates one item at a time as the collection is consumed — peak memory stays flat no matter how many rows flow through:

```php
foreach (UserData::lazyCollection($csvRows) as $user) {
    $importer->process($user); // ~12 KB peak at 100k rows — flat regardless of row count
}
```

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
// In Laravel — call fromRequest() yourself, auto-validates
$data = CreateOrderData::fromRequest($request);

// Or register SimpleDataObjectsServiceProvider (opt-in, not auto-discovered)
// and skip FormRequest entirely:
public function store(CreateOrderData $data) { /* already validated */ }

// Standalone — no Laravel app needed
CreateOrderData::validate($rawArray); // throws ValidationException
```

### Never-throw error accumulation

`fromResult()` tries every field instead of stopping at the first one, with dot-paths for nested DTOs and collections:

```php
$result = CreateOrderData::fromResult($request->all());

$result->ok();       // bool
$result->errors();   // ['deliveryDate' => 'Invalid date format', 'items.2.price' => '...']
$result->value();    // CreateOrderData — throws if !ok()

// fromValidatedResult() merges in #[Rules] failures the same way
```

---

### Optional — PATCH semantics

`Optional` distinguishes "key not sent" from "key sent as `null`" — an omitted field means leave it untouched, not clear it:

```php
class UpdateUserData extends BaseData
{
    public function __construct(
        public readonly string|Optional $name,
        public readonly string|Optional|null $bio,   // null = clear it, Optional = don't touch it
    ) {}
}

$data = UpdateUserData::from(['bio' => null]);
$data->definedOnly();   // ['bio' => null] — 'name' stays absent
$model->update($data->definedOnly());
```

---

### Schema generation — JSON Schema & TypeScript

`jsonSchema()` walks the same metadata as `from()`/`toArray()` — no extra reflection — into a JSON Schema (draft 2020-12). `TypeScriptGenerator`/`bin/sdo-typescript` build on top of it for `.d.ts` output:

```php
OrderData::jsonSchema();
// ['type' => 'object', 'properties' => [...], 'required' => [...], '$defs' => [...]]
```

```sh
vendor/bin/sdo-typescript resources/js/types/data-objects.d.ts app/Data
```

```ts
export interface OrderData {
  id: number;
  shippingAddress: AddressData;
  status: 'pending' | 'shipped' | 'cancelled';
}
```

---

## All Attributes

| Attribute | Where | Effect |
|---|---|---|
| `#[Cast(new DateTimeCast('Y-m-d'))]` | property | type conversion on hydration + serialization |
| `#[Rules(['required', 'email'])]` | property | Laravel validation rules |
| `#[InferRules]` | class | auto-infer validation rules from property types |
| `#[Pipe(TrimValuePipe::class)]` | property | value-level preprocessing pipeline |
| `#[Pipe(TrimStringsPipe::class)]` | class | array-level preprocessing pipeline |
| `#[Flatten]` | property | inline nested DTO fields into parent |
| `#[Hidden(except: ['admin'])]` | property | exclude from `toArray()` / JSON, optionally per `toArray(context:)` |
| `#[IgnoreIfNull]` | property | omit from output when `null` |
| `#[Computed]` | method | add a derived, method-backed field to `toArray()` |
| `#[MapPropertyName('input_key', ...)]` | property | map input key(s) (aliases) → property, same name on output |
| `#[MapInputName]` / `#[MapOutputName]` | property | map hydration and serialization keys independently |
| `#[TransformKeys(TransformKeys::SNAKE_CASE)]` | class | transform all keys at class level |
| `#[DataCollection(ItemData::class)]` | property | typed collection of DTOs |
| `#[Discriminator('type', ['card' => CardData::class])]` | abstract class | polymorphic hydration — `from()` picks the subclass by field value |
| `#[WrapIn('data')]` | class | wrap `toResponse()`'s payload under a key |

## Built-in Casts

`DateTimeCast` · `DateTimeImmutableCast` · `EnumCast` · `BooleanCast` · `IntegerCast` · `FloatCast` · `TrimCast` · `JsonCast` · `EncryptedCast` (XSalsa20-Poly1305)

---

## Contributing

This library is actively developed. Missing a feature you need? Open a
[feature request](https://github.com/std-out/simple-data-objects/issues) or start a
[discussion](https://github.com/std-out/simple-data-objects/discussions) — happy to add it.

Bug reports, feature ideas, and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md)
for the dev setup (one `make build` away) and the quality bar (100% coverage, enforced in CI).

Release history lives in the [CHANGELOG](CHANGELOG.md).

## License

MIT — see [LICENSE](LICENSE).
