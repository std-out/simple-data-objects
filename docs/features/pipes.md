# DataPipe — Input Preprocessing

Pipes transform input data **before casting and hydration**. They form a middleware chain — each pipe calls `$next` to pass control to the next one.

Two types:
- **`DataPipe`** — class-level: receives the full input `array` and returns a transformed `array`
- **`ValuePipe`** — property-level: receives a single `mixed` value and returns the transformed value

## Class-level — `DataPipe`

Place `#[Pipe(...)]` on the class. The pipe runs on the entire input array before any field is resolved.

```php
use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\Pipes\TrimStringsPipe;
use StdOut\SimpleDataObjects\Pipes\NullifyEmptyStringsPipe;

#[Pipe(TrimStringsPipe::class, NullifyEmptyStringsPipe::class)]
class ContactData extends BaseData
{
    public function __construct(
        public readonly string  $name,
        public readonly ?string $bio = null,
    ) {}
}

ContactData::from(['name' => '  Alice  ', 'bio' => '']);
// name = 'Alice', bio = null  ← both pipes ran on the array
```

### Implement `DataPipe`

```php
use StdOut\SimpleDataObjects\Contracts\DataPipe;

final class SanitizeHtmlPipe implements DataPipe
{
    public function handle(array $data, string $dataClass, callable $next): array
    {
        return $next(array_map(
            static fn ($v) => is_string($v) ? strip_tags($v) : $v,
            $data,
        ));
    }
}
```

## Property-level — `ValuePipe`

Place `#[Pipe(...)]` on a constructor parameter. The pipe runs only on that field's raw value.

```php
use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\Pipes\TrimValuePipe;
use StdOut\SimpleDataObjects\Pipes\NullifyEmptyStringValuePipe;

class ProfileData extends BaseData
{
    public function __construct(
        #[Pipe(TrimValuePipe::class)]
        public readonly string $name,

        public readonly string $email,          // no pipe — untouched

        #[Pipe(TrimValuePipe::class, NullifyEmptyStringValuePipe::class)]
        public readonly ?string $bio = null,    // trim → nullify
    ) {}
}

ProfileData::from(['name' => '  Alice  ', 'email' => ' a@b.com ', 'bio' => '   ']);
// name = 'Alice'        ← trimmed
// email = ' a@b.com '  ← NOT trimmed (no pipe on this property)
// bio = null            ← '   ' → '' (trim) → null (nullify)
```

### Implement `ValuePipe`

```php
use StdOut\SimpleDataObjects\Contracts\ValuePipe;

final class SlugifyPipe implements ValuePipe
{
    public function handle(mixed $value, string $paramName, callable $next): mixed
    {
        return $next(is_string($value) ? strtolower(str_replace(' ', '-', $value)) : $value);
    }
}
```

## Chaining

Pipes run **left to right** in declaration order:

```php
#[Pipe(TrimValuePipe::class, NullifyEmptyStringValuePipe::class)]
//         ↑ runs first              ↑ runs second
```

## Execution Order

```
Input array
    ↓
#[Pipe] on class (DataPipe) — each pipe in order
    ↓
For each property:
    raw value from array
        ↓
    #[Pipe] on property (ValuePipe) — each pipe in order
        ↓
    #[Cast] get()
        ↓
    typed property value
```

## Built-in Pipes

| Class | Type | Effect |
|---|---|---|
| `TrimStringsPipe` | `DataPipe` | `trim()` every string in the input array |
| `NullifyEmptyStringsPipe` | `DataPipe` | `'' → null` for every value in the array |
| `TrimValuePipe` | `ValuePipe` | `trim()` the individual field value |
| `NullifyEmptyStringValuePipe` | `ValuePipe` | `'' → null` for the individual field value |

## Performance

Pipes have **zero overhead when not configured**. The pipeline check is an `!== []` guard before any function call — unused DTOs pay nothing.

When pipes are present, the middleware chain uses `array_reduce` — O(n) in the number of pipes, executed once per hydration call.
