# Built-in Casts Overview

Casts implement `CastsValue` with two methods:
- `get(mixed $value): mixed` — transforms raw input → typed PHP value (hydration)
- `set(mixed $value): mixed` — transforms PHP value → serializable form (serialization)

## Quick Reference

| Cast | Hydration result | Serialization output |
|---|---|---|
| [`DateTimeCast`](/casts/date-time) | `DateTime` | formatted string |
| [`DateTimeImmutableCast`](/casts/date-time) | `DateTimeImmutable` | formatted string |
| [`EnumCast`](/casts/enum) | `BackedEnum` instance | backing value |
| [`BooleanCast`](/casts/boolean) | `bool` | `bool` |
| [`IntegerCast`](/casts/numeric) | `int` | `int` |
| [`FloatCast`](/casts/numeric) | `float` | `float` |
| [`TrimCast`](/casts/trim) | trimmed `string` | trimmed `string` |
| [`JsonCast`](/casts/json) | `array` | JSON `string` |
| [`EncryptedCast`](/casts/encrypted) | plaintext `string` | encrypted ciphertext |

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

See [Custom Casts →](/casts/custom) for implementing your own.
