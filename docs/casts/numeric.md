# IntegerCast & FloatCast

Cast string or numeric input to PHP `int` or `float`. Useful when consuming form data or query parameters where numbers arrive as strings.

## IntegerCast

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\IntegerCast;

class PaginationData extends BaseData
{
    public function __construct(
        #[Cast(new IntegerCast)]
        public readonly int $page,

        #[Cast(new IntegerCast)]
        public readonly int $perPage,
    ) {}
}

$pagination = PaginationData::from(['page' => '2', 'perPage' => '25']);

$pagination->page;    // 2 (int)
$pagination->perPage; // 25 (int)
```

## FloatCast

```php
use StdOut\SimpleDataObjects\Casts\FloatCast;

class PriceData extends BaseData
{
    public function __construct(
        #[Cast(new FloatCast)]
        public readonly float $amount,
    ) {}
}

PriceData::from(['amount' => '9.99'])->amount; // 9.99 (float)
```

### Rejecting INF and NAN

`json_decode('1e400')` yields `INF`, and a float field accepts it — then `toJson()` fails with `JsonException`. Pass `finite: true` to reject non-finite values at hydration:

```php
#[Cast(new FloatCast(finite: true))]
public readonly float $amount,
```

`INF`, `-INF` and `NAN` then throw `InvalidArgumentException`, and `tryFrom()` returns `null`. Without `finite` they pass through unchanged.

## Null Handling

Both casts return `null` for `null` input.

## Serialization

`set()` returns the value as-is (`int` / `float`). Serialized to JSON numbers.

## Note

You generally don't need these casts when your PHP property is already typed `int` / `float` and the input values are already numeric — PHP's type coercion handles it. Use these casts when you want **explicit** control or when the input is a string from a query parameter / form field.
