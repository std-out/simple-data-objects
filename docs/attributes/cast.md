# #[Cast]

Applies a cast to a constructor parameter during hydration (`get()`) and serialization (`set()`).

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\Cast;

#[Cast(new SomeCast(...))]
public readonly SomeType $property,
```

## Example

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;
use StdOut\SimpleDataObjects\Casts\BooleanCast;

class EventData extends BaseData
{
    public function __construct(
        public readonly string $name,

        #[Cast(new DateTimeCast('Y-m-d'))]
        public readonly DateTime $startsAt,

        #[Cast(new BooleanCast)]
        public readonly bool $isPublic,
    ) {}
}

$event = EventData::from([
    'name'     => 'PHP Conf',
    'startsAt' => '2025-06-15',    // string → DateTime on hydration
    'isPublic' => 'yes',           // string → true on hydration
]);

$event->toArray()['startsAt']; // '2025-06-15' (DateTime → string on serialization)
$event->toArray()['isPublic']; // true
```

## Constraints

- Only one `#[Cast]` per parameter is allowed.
- Cannot be combined with `#[DataCollection]` or `#[Flatten]` on the same parameter.

## Built-in Casts

See [Built-in Casts →](../casts/index.md) for the full list.

## Custom Casts

Implement `CastsValue`:

```php
use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class UpperCaseCast implements CastsValue
{
    public function get(mixed $value): ?string
    {
        return $value === null ? null : strtoupper((string) $value);
    }

    public function set(mixed $value): ?string
    {
        return $value === null ? null : strtoupper((string) $value);
    }
}
```

See [Custom Casts →](../casts/custom.md).
