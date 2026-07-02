# Built-in Casts Overview

Casts implement `CastsValue` with two methods:
- `get(mixed $value): mixed` — transforms raw input → typed PHP value (hydration)
- `set(mixed $value): mixed` — transforms PHP value → serializable form (serialization)

## Quick Reference

| Cast | Hydration result | Serialization output |
|---|---|---|
| [`DateTimeCast`](./date-time.md) | `DateTime` | formatted string |
| [`DateTimeImmutableCast`](./date-time.md) | `DateTimeImmutable` | formatted string |
| [`EnumCast`](./enum.md) | `BackedEnum` instance | backing value |
| [`BooleanCast`](./boolean.md) | `bool` | `bool` |
| [`IntegerCast`](./numeric.md) | `int` | `int` |
| [`FloatCast`](./numeric.md) | `float` | `float` |
| [`TrimCast`](./trim.md) | trimmed `string` | trimmed `string` |
| [`JsonCast`](./json.md) | `array` | JSON `string` |
| [`EncryptedCast`](./encrypted.md) | plaintext `string` | encrypted ciphertext |

## Applying Casts

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;

class EventData extends BaseData
{
    public function __construct(
        #[Cast(new DateTimeCast('Y-m-d H:i:s'))]
        public readonly DateTime $createdAt,
    ) {}
}
```

## CastsValue Interface

```php
interface CastsValue
{
    public function get(mixed $value): mixed;
    public function set(mixed $value): mixed;
}
```

See [Custom Casts →](./custom.md) for implementing your own.
