# EnumCast

Casts raw values to `BackedEnum` instances with configurable fallback behavior.

## Usage

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\EnumCast;

enum Status: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}

class UserData extends BaseData
{
    public function __construct(
        #[Cast(new EnumCast(Status::class))]
        public readonly Status $status,
    ) {}
}
```

## Without EnumCast

For simple cases, you can omit the cast entirely — the hydrator resolves `BackedEnum` from its backing value automatically:

```php
class UserData extends BaseData
{
    public function __construct(
        public readonly Status $status, // string 'active' → Status::Active
    ) {}
}
```

Use `EnumCast` explicitly when you need a fallback value.

## With a Fallback

```php
#[Cast(new EnumCast(Status::class, default: Status::Inactive))]
public readonly Status $status,
```

If the input value does not match any enum case, `Status::Inactive` is returned instead of throwing. Without `default`, `null` and unknown values become `null`, so the property must be nullable.

## Constructor Parameters

```php
new EnumCast(
    enumClass: Status::class,
    default:   null,           // ?UnitEnum returned for null and unknown values
)
```

## Serialization

`set()` returns the enum's backing value (`string` or `int`):

```php
$user = UserData::from(['status' => 'active']);
$user->toArray()['status']; // 'active'
```
