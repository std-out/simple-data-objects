# DateTimeCast / DateTimeImmutableCast

Parse date/time strings into `DateTime` or `DateTimeImmutable` objects during hydration; format them back to strings during serialization.

## Usage

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\DateTimeCast;
use StdOut\SimpleDataObjects\Casts\DateTimeImmutableCast;

class EventData extends BaseData
{
    public function __construct(
        #[Cast(new DateTimeCast('Y-m-d'))]
        public readonly DateTime $startsAt,

        #[Cast(new DateTimeImmutableCast('Y-m-d H:i:s', timezone: 'UTC'))]
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
```

## Constructor Parameters

```php
new DateTimeCast(
    outputFormat: 'Y-m-d',          // format used by toArray() / set()
    inputFormat:  null,             // format to parse input; null = auto-detect
    timezone:     'UTC',            // string or DateTimeZone, null = system default
)
```

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$outputFormat` | `string` | required | PHP date format string for serialization |
| `$inputFormat` | `?string` | `null` | PHP date format to parse input; `null` uses `new DateTime(...)` (flexible) |
| `$timezone` | `DateTimeZone\|string\|null` | `null` | Timezone for parsed values |

## Examples

```php
// Auto-detect input format
#[Cast(new DateTimeCast('Y-m-d'))]
// '2025-06-15', '15/06/2025', '2025-06-15T12:00:00Z' all parse correctly

// Strict input format
#[Cast(new DateTimeCast('Y-m-d', inputFormat: 'd/m/Y'))]
// Only '15/06/2025' is accepted

// With timezone
#[Cast(new DateTimeCast('Y-m-d H:i:s', timezone: 'Europe/Kyiv'))]
```

## Null Handling

Returns `null` if the input is `null`. Throws `InvalidArgumentException` if the input is non-null but unparseable.

## File Cache Compatibility

Both `DateTimeCast` and `DateTimeImmutableCast` are fully file-cacheable. Timezone is stored as a string (not `DateTimeZone` object) to ensure `var_export()` / `__set_state()` round-trips safely.
