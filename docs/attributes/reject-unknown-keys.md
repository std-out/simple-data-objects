# #[RejectUnknownKeys]

Rejects hydration when the input contains a key the class doesn't recognize — the equivalent of serde's `deny_unknown_fields`, pydantic's `extra='forbid'`, or zod's `.strict()`.

Without it, an unexpected key (a typo, a stale field from an old API contract, a client sending more than the endpoint expects) is silently ignored. With it, hydration throws instead.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\RejectUnknownKeys;

#[RejectUnknownKeys]
class CreateOrderData extends BaseData
{
    public function __construct(
        public readonly string $title,
        public readonly int $amount,
    ) {}
}
```

```php
CreateOrderData::from(['title' => 'Widget', 'amount' => 5]);
// OK

CreateOrderData::from(['title' => 'Widget', 'amount' => 5, 'ammount' => 5]);
// DataHydrationException: Unknown key ['ammount'] for CreateOrderData.
```

## What counts as "known"

A key is known if it matches one of a parameter's **input names** — the name(s) hydration actually reads, after [`#[MapPropertyName]`](./map-property-name.md)/[`#[MapInputName]`/`#[MapOutputName]`](./map-input-output-name.md) or a class-level [`#[TransformKeys]`](./transform-keys.md) strategy has been applied. Every alias is known, and so is the output key (for the `from(toArray())` roundtrip) — the underlying PHP property name is irrelevant if it's been renamed:

```php
#[RejectUnknownKeys]
class UserData extends BaseData
{
    public function __construct(
        #[MapPropertyName('user_email')]
        public readonly string $email,
    ) {}
}

UserData::from(['user_email' => '...']); // known
UserData::from(['email' => '...']);      // unknown — 'email' was never the input name
```

A [`#[Computed]`](./computed.md) field's output key is known too, so passing it back through `from(toArray())` doesn't trip the check.

`#[Hidden]`, `#[IgnoreIfNull]`, and `#[WhenLoaded]` parameters are still known input keys — those attributes only affect output or `fromModel()`, not what `from()` accepts.

## The exception

`DataHydrationException::$unknownKeys` exposes the offending keys as a plain array, so callers can inspect them without parsing the message:

```php
try {
    CreateOrderData::from($input);
} catch (DataHydrationException $e) {
    $e->unknownKeys; // ['ammount']
}
```

The check runs against the caller's raw input, before any class-level [`#[Pipe]`](./pipe.md) transformation and before individual fields are checked for missing values — an input with both an unknown key and a missing required field reports the unknown key first.

## Incompatible with #[Flatten] and #[Discriminator]

Both combinations are rejected at metadata-build time, not silently ignored:

- **`#[Flatten]`** inlines a nested DTO's own fields into the parent's input — the parent class has no way to know which keys belong to it versus the flattened nested DTO, so there's nothing correct to check against.
- **`#[Discriminator]`** classes never hydrate themselves; the concrete subclass does. Declare `#[RejectUnknownKeys]` on the subclasses instead.

## Standalone constructor-less, hybrid, and lazy classes

Works the same on [constructor-less and hybrid DTOs](../features/hydration.md#constructor-less-dtos) — the known-key set includes properties populated via constructor injection and via post-construction assignment alike. With [`fromLazy()`](../features/hydration.md#lazy-hydration), the check runs on first property access, same as any other hydration error.
