# #[Pipe]

Attaches a preprocessing pipeline to a class (class-level) or a single constructor parameter (property-level).

## Class-level (`DataPipe`)

Transforms the **entire input array** before any field is resolved.

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
```

The class implementing the pipe must implement `DataPipe`:

```php
interface DataPipe
{
    public function handle(array $data, string $dataClass, callable $next): array;
}
```

## Property-level (`ValuePipe`)

Transforms the **individual raw value** for that parameter only.

```php
class ProfileData extends BaseData
{
    public function __construct(
        #[Pipe(TrimValuePipe::class)]
        public readonly string $name,

        public readonly string $email, // no pipe

        #[Pipe(TrimValuePipe::class, NullifyEmptyStringValuePipe::class)]
        public readonly ?string $bio = null,
    ) {}
}
```

The class implementing the pipe must implement `ValuePipe`:

```php
interface ValuePipe
{
    public function handle(mixed $value, string $paramName, callable $next): mixed;
}
```

## Multiple pipes

Pass multiple pipe classes — they execute **left to right**:

```php
#[Pipe(TrimValuePipe::class, NullifyEmptyStringValuePipe::class)]
//         1st ↑                     2nd ↑
```

## See Also

- [DataPipe feature guide →](../features/pipes.md)
- [Custom Casts →](../casts/custom.md) — if you need two-directional transformation (hydration + serialization)
