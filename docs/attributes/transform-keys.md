# #[TransformKeys]

Transforms all input keys at the class level using a named strategy. Applied before property lookup during hydration, and to property names during serialization.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\TransformKeys;

#[TransformKeys('snake')]    // or 'camel', 'studly', 'kebab'
class MyData extends BaseData { ... }
```

## Strategies

| Strategy | Example input | After transform |
|---|---|---|
| `snake` | `firstName` | `first_name` |
| `camel` | `first_name` | `firstName` |
| `studly` | `first_name` | `FirstName` |
| `kebab` | `firstName` | `first-name` |

## Example: camelCase API → snake_case input

Your PHP properties are `snake_case`, but the JSON payload uses `camelCase`:

```php
#[TransformKeys('snake')]
class EventData extends BaseData
{
    public function __construct(
        public readonly string $event_name,
        public readonly string $start_date,
        public readonly int    $ticket_count,
    ) {}
}

$data = EventData::from([
    'eventName'   => 'PHP Conf',
    'startDate'   => '2025-06-15',
    'ticketCount' => 500,
]);

$data->event_name;   // 'PHP Conf'
$data->start_date;   // '2025-06-15'
```

### Serialization mirrors the transform

```php
$data->toArray();
// [
//   'eventName'   => 'PHP Conf',
//   'startDate'   => '2025-06-15',
//   'ticketCount' => 500,
// ]
```

## Interaction with #[MapPropertyName]

`#[MapPropertyName]` on individual parameters takes precedence over `#[TransformKeys]`. Use `#[MapPropertyName]` to override specific keys when most keys follow the class-level transform.
