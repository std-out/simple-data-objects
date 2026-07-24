# Hydration

Hydration converts raw input into a typed DTO instance. The primary factory method is `from()` — a **universal factory**: give it whatever you have, and it works out how to hydrate from it.

## Input Formats

### Array

```php
UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
```

### Eloquent model / any Arrayable

```php
UserData::from($eloquentModel);   // uses $model->toArray()
UserData::from(collect([...]));   // Laravel Collection
```

### stdClass

```php
UserData::from((object) ['name' => 'Alice', 'email' => 'alice@example.com']);
```

### JsonSerializable

```php
UserData::from($someJsonSerializableObject);
```

### Any Traversable

Generators, iterators — anything `foreach`-able that yields key/value pairs:

```php
UserData::from($generatorYieldingFields);
```

### JSON String

```php
UserData::from('{"name":"Alice","email":"alice@example.com"}');
UserData::fromJson('{"name":"Alice"}'); // explicit variant, same behavior
```

JSON depth is limited to **32 levels** to prevent stack exhaustion on adversarial input. A string that isn't a JSON object throws `DataHydrationException`.

### Plain object

Any other object hydrates from its **public properties**:

```php
$row = new ReportRow(); // public $name, public $email
UserData::from($row);
```

### An existing instance

Passing an instance of the same class returns it **as-is** — instances are immutable, so no copy is needed:

```php
UserData::from($user) === $user; // true
```

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

## Lazy Hydration

`fromLazy()` returns a native PHP 8.4 **lazy ghost**: a real instance of your class whose hydration runs only when a property is first read. When a request creates many DTOs but only touches some of them, the untouched ones cost almost nothing:

```php
$user = UserData::fromLazy($row);   // no hydration happened yet

$user->name;                        // ← hydrates here, once
$user instanceof UserData;          // true — a real instance, not a proxy
```

Everything is transparent after the first access — casts, pipes, `toArray()`, `equals()` behave exactly as with `from()`. Two things to keep in mind:

- **Validation of input is deferred too**: invalid data throws `DataHydrationException` at the first property access, not at `fromLazy()`. Use `from()` when you want failures at the construction site.
- **The win depends on how expensive hydration is.** Measured with 10% of objects actually read: ~3× faster for a cast-heavy DTO (`json_decode`, trims, rounding), ~6× for a DTO holding a collection of 20 nested DTOs — but for a tiny flat DTO plain `from()` is already so cheap that creating the ghost costs about the same as hydrating it. Rule of thumb: reach for `fromLazy()` when DTOs carry casts, nested objects, or collections *and* not every instance is consumed.

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

## Constructor-less DTOs

A constructor isn't required. Plain typed property declarations work the same way — hydrated via direct assignment instead of constructor injection:

```php
class SettingsData extends BaseData
{
    public string $theme = 'light';   // optional — has a default
    public ?string $locale = null;    // optional — nullable
    public readonly string $userId;   // required — no default, not nullable
}

$settings = SettingsData::from(['userId' => 'u_123', 'theme' => 'dark']);

$settings->theme;    // 'dark'
$settings->locale;   // null
$settings->userId;   // 'u_123'
```

The same rules apply as for constructor parameters: a property with no default and a non-nullable type is required and throws `DataHydrationException` when missing; `readonly` properties are supported (including via `with()` and `fromLazy()`); and the full attribute set — `#[Cast]`, `#[DataCollection]`, `#[Flatten]`, `#[Hidden]`, `#[IgnoreIfNull]`, `#[MapPropertyName]`, `#[Pipe]`, `#[Rules]` — works identically on a property as it does on a constructor parameter.

Only **public, non-static, typed** properties are picked up. Static properties, `private`/`protected` properties, and untyped properties (`public $x;`) are ignored — declare them normally for internal bookkeeping without affecting hydration or `toArray()`.

## Hybrid DTOs

A class can mix both styles: a constructor for some fields, plain properties for others. Both are hydrated together in one `from()` call:

```php
class OrderData extends BaseData
{
    public function __construct(
        public readonly string $id,
        public readonly string $status = 'pending',
    ) {}

    // Extra fields, outside the constructor — hydrated the same way
    public ?string $internalNote = null;
    public readonly string $trackingId;
}

$order = OrderData::from([
    'id'         => 'ORD-1',
    'trackingId' => 'TRK-9',
]);

$order->status;      // 'pending' — constructor default
$order->trackingId;  // 'TRK-9'   — extra property, required (no default)
```

This is useful for adding fields to an existing constructor-based DTO without touching the constructor's signature (and its call sites). There's no functional difference between a field declared in the constructor and one declared as a plain property — pick whichever reads more naturally for a given class.
