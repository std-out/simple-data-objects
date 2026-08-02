# #[InferRules]

Auto-generates [`#[Rules]`](./rules.md)-equivalent validation rules from each property's PHP type — the equivalent of serde's derived validation, pydantic's implicit field validation, or zod's schema-from-type inference.

Opt-in per class. Without it, `validate()` and `fromValidated()` only enforce rules you declared explicitly — this is unchanged, existing behavior.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\InferRules;

#[InferRules]
class CreateOrderData extends BaseData
{
    public function __construct(
        public readonly string $title,           // → ['required', 'string']
        public readonly int $amount,              // → ['required', 'integer']
        public readonly ?string $note = null,     // → ['nullable', 'string']
    ) {}
}
```

## Type → rule mapping

| PHP type | Inferred rule |
|---|---|
| `string` | `string` |
| `int` | `integer` |
| `float` | `numeric` |
| `bool` | `boolean` |
| `array` | `array` |
| `BackedEnum` / `UnitEnum` | `Rule::enum(...)` |
| nested `BaseData` | `array` (plus cascaded rules, see below) |
| `#[DataCollection]` | `array` (plus cascaded rules, see below) |
| any other / no type | *(no type rule — only presence)* |

Every inferred rule is prefixed with `required` or `nullable`, based on the property's own nullability (a nullable type, e.g. `?string`) — not on whether it has a default value.

For a scalar union type (e.g. `int|string`), the type rule comes from whichever builtin member PHP's reflection API reports first — this is **not** guaranteed to match declaration order. Declare `#[Rules]` explicitly for union-typed properties where the exact rule matters.

## Nested DTOs and collections cascade dot-notation

A nested `BaseData` property gets `['required'|'nullable', 'array']` for itself, plus the nested class's **own** validation rules (inferred or explicit) cascaded under a dot-prefixed key:

```php
#[InferRules]
class AddressData extends BaseData
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

#[InferRules]
class CreateOrderData extends BaseData
{
    public function __construct(
        public readonly AddressData $address,
    ) {}
}

// CreateOrderData's inferred rules:
// 'address'        => ['required', 'array']
// 'address.street' => ['required', 'string']
// 'address.city'   => ['required', 'string']
```

A `#[DataCollection]` property cascades the same way, under a `*.` prefix:

```php
#[DataCollection(ItemData::class)]
public readonly TypedDataCollection $items;

// 'items'         => ['required', 'array']
// 'items.*.price' => ['required', 'numeric']
```

The cascade only happens if the **nested** class itself has rules to contribute — a nested class without `#[InferRules]` and without any `#[Rules]` attributes contributes nothing beyond the parent's own `'field' => ['required'|'nullable', 'array']` entry. Nesting depth is unbounded (a nested class's own cascade recurses into its own nested classes), and self-referential or mutually cyclic `BaseData` graphs are detected and stop cascading at the cycle instead of recursing forever.

## Overriding or merging with #[Rules]

By default, an explicit [`#[Rules]`](./rules.md) on a property **replaces** the inferred rules for that property entirely — predictable, no surprises about what's actually being merged:

```php
#[InferRules]
class CreateOrderData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'email:rfc,dns'])]
        public readonly string $title,   // inferred ['required', 'string'] is discarded
    ) {}
}
```

Pass `merge: true` to append the explicit rules to the inferred ones instead:

```php
#[Rules(['max:100'], merge: true)]
public readonly string $title,   // → ['required', 'string', 'max:100']
```

## Why opt-in

Turning this on for every `BaseData` class by default would silently start validating classes that previously had no rules at all — a breaking change to `fromValidated()`/`validate()` behavior on upgrade. `#[InferRules]` keeps it explicit and per-class, the same reasoning as [`#[RejectUnknownKeys]`](./reject-unknown-keys.md).
