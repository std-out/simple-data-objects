# Optional — Absent vs Null

A missing input key normally resolves to a default, `null`, or a thrown error — there's no way to tell "not sent" apart from "sent as `null`". `Optional` fills that gap: a sentinel union member on the property type, for PATCH-style partial updates where an omitted field must mean "leave untouched", not "clear it".

```php
use StdOut\SimpleDataObjects\Optional;

class UpdateUserData extends BaseData
{
    public function __construct(
        public readonly string|Optional $name,
        public readonly string|Optional|null $bio,   // null = clear it, Optional = don't touch it
    ) {}
}

$data = UpdateUserData::from(['bio' => null]);
Optional::isMissing($data->name); // true — key wasn't sent
$data->bio;                       // null — key was sent, explicitly cleared
```

## Usage

| Input state | `string\|Optional $name` | `string\|Optional\|null $bio` |
|---|---|---|
| key absent | `Optional::missing()` | `Optional::missing()` |
| key present, `null` | `TypeError` (same as any non-nullable type today) | `null` |
| key present, value | the value | the value |

Check a property with `Optional::isMissing($value)` (or `$value instanceof Optional`).

## Serialization

A still-missing `Optional` field is **always** omitted from `toArray()` (and everything built on it: `toJson()`, `jsonSerialize()`, `only()`, `except()`, `diff()`, `equals()`) — unconditionally, not opt-in like [`#[IgnoreIfNull]`](../attributes/ignore-if-null.md). An explicit `null` still serializes as `key => null`.

```php
$data = UpdateUserData::from(['bio' => null]);
$data->toArray(); // ['bio' => null] — no 'name'
```

This keeps `from($dto->toArray())` roundtrip-safe: an omitted key re-hydrates back to `Optional::missing()`.

## with() and definedOnly()

`with()` copies a missing field through unchanged, and can explicitly reset a field back to missing:

```php
$data->with(name: Optional::missing());
```

`definedOnly()` is `toArray()` under a name that reads clearly at PATCH call sites:

```php
$model->update($data->definedOnly());
```

## Validation (#[InferRules])

An `Optional` field infers a `sometimes` presence rule instead of `required`/`nullable`, so [`#[InferRules]`](../attributes/infer-rules.md) doesn't force every PATCH field to be present. A nullable-optional field gets both:

```php
// string|Optional $name       => ['sometimes', 'string']
// string|Optional|null $bio   => ['sometimes', 'nullable', 'string']
```

## Nested and collection fields

`Optional` works the same way on a nested `BaseData` or `#[DataCollection]` property — missing resolves to `Optional::missing()`, present hydrates (and validates, via `fromResult()`) normally.

## Edge cases

- `Optional` cannot be combined with a declared default value — a missing key already resolves to `Optional::missing()`, so a default would be ambiguous. Throws at metadata-build time.
- `Optional` cannot be combined with [`#[Flatten]`](../attributes/flatten.md) — a flattened field has no single key to be absent.
- [`#[WhenLoaded]`](../attributes/when-loaded.md) synergy is automatic: an unloaded relation is already a genuinely absent key, so `CustomerData|Optional $customer` resolves to `Optional::missing()` with no extra configuration.
