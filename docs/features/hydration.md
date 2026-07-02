# Hydration

Hydration converts raw input into a typed DTO instance. The primary factory method is `from()`.

## Input Formats

### Array

```php
UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
```

### stdClass

```php
UserData::from((object) ['name' => 'Alice', 'email' => 'alice@example.com']);
```

### Arrayable (e.g. Laravel Collection, Model)

```php
UserData::from(collect(['name' => 'Alice', 'email' => 'alice@example.com']));
UserData::from($eloquentModel); // uses $model->toArray()
```

### JsonSerializable

```php
UserData::from($someJsonSerializableObject);
```

### JSON String

```php
UserData::fromJson('{"name":"Alice","email":"alice@example.com"}');
```

JSON depth is limited to **32 levels** to prevent stack exhaustion on adversarial input.

## Safe Factory — tryFrom()

Returns `null` instead of throwing when input is missing required fields or fails casting:

```php
$user = UserData::tryFrom($request->all()); // ?UserData

if ($user === null) {
    return response()->json(['error' => 'Invalid data'], 422);
}
```

::: tip
Use `tryFrom()` when you want to handle bad input gracefully. Use `from()` for trusted internal data.
:::

## Nested DTOs

Type-hint a property as another `BaseData` subclass and it is hydrated automatically:

```php
class OrderData extends BaseData
{
    public function __construct(
        public readonly string   $id,
        public readonly UserData $customer,
    ) {}
}

$order = OrderData::from([
    'id'       => 'ORD-1',
    'customer' => ['name' => 'Alice', 'email' => 'alice@example.com'],
]);

$order->customer->name; // 'Alice'
```

Nested DTOs can be nested further — any depth is supported.

## Enums

`BackedEnum` values are cast from their backing scalar automatically. Pure (non-backed) `UnitEnum` values are matched by **case name**:

```php
enum Status: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}

enum Priority // no backing type
{
    case Low;
    case High;
}

class OrderData extends BaseData
{
    public function __construct(
        public readonly Status $status,      // from 'active'
        public readonly Priority $priority,  // from 'High' (case name)
    ) {}
}

$order = OrderData::from(['status' => 'active', 'priority' => 'High']);
$order->status;   // Status::Active
$order->priority; // Priority::High
```

Already-instantiated enum values pass through unchanged.

### Invalid Enum Values

A value that matches no case throws a `DataHydrationException` with the field name for a **required** parameter, and resolves to `null` for a **nullable** one:

```php
OrderData::from(['status' => 'bogus', 'priority' => 'High']);
// throws DataHydrationException: Invalid value 'bogus' for field 'status'; expected a case of Status.
```

For a fallback value instead of an exception, use [`EnumCast`](../casts/enum.md).

## Optional Fields

Fields with default values or nullable type are optional:

```php
class UserData extends BaseData
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $email,
        public readonly ?string $phone = null,    // optional — defaults to null
        public readonly int     $age   = 0,       // optional — defaults to 0
    ) {}
}

UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
// phone = null, age = 0
```

## Missing Required Fields

If a required field (non-nullable, no default) is missing, `DataHydrationException` is thrown:

```php
UserData::from(['name' => 'Alice']); // throws — 'email' is required
```
