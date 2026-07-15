# UuidCast

Validates that a string is an RFC 4122 UUID and normalizes it to lowercase, on both hydration and serialization.

## Usage

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\UuidCast;

class OrderData extends BaseData
{
    public function __construct(
        #[Cast(new UuidCast)]
        public readonly string $id,
    ) {}
}

$order = OrderData::from(['id' => '9D3B1F6E-2C4A-4B8E-9F0A-1234567890AB']);

$order->id; // '9d3b1f6e-2c4a-4b8e-9f0a-1234567890ab'
```

## Null Handling

Returns `null` for `null` input.

## Invalid Input

Throws `InvalidArgumentException` for any string that doesn't match the RFC 4122 UUID format (`8-4-4-4-12` hex digits).

```php
OrderData::from(['id' => 'not-a-uuid']); // throws InvalidArgumentException
```

::: tip
The core package stays dependency-light, so `UuidCast` only validates format and does not require `ramsey/uuid` or `symfony/uid`. If you need a rich UUID object instead of a string, wrap one of those libraries in a [custom cast](./custom.md).
:::
