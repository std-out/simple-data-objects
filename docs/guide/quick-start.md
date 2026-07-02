# Quick Start

## 1. Define a DTO

Extend `BaseData` and declare constructor properties:

```php
use StdOut\SimpleDataObjects\BaseData;

class UserData extends BaseData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}
}
```

## 2. Hydrate

```php
$user = UserData::from([
    'name'  => 'Alice',
    'email' => 'alice@example.com',
]);

$user->name;  // 'Alice'
$user->phone; // null
```

## 3. Serialize

```php
$user->toArray(); // ['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => null]
$user->toJson();  // '{"name":"Alice","email":"alice@example.com","phone":null}'
$user->only('name');          // ['name' => 'Alice']
$user->except('phone');       // ['name' => 'Alice', 'email' => 'alice@example.com']
```

## 4. Add Validation

```php
use StdOut\SimpleDataObjects\Attributes\Rules;

class UserData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string', 'max:100'])]
        public readonly string $name,

        #[Rules(['required', 'email:rfc'])]
        public readonly string $email,

        #[Rules(['nullable', 'string'])]
        public readonly ?string $phone = null,
    ) {}
}

// Throws Illuminate\Validation\ValidationException on failure
$user = UserData::fromValidated($request->all());
```

## 5. Add Type Casting

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
        public readonly bool $isPublic = false,
    ) {}
}

$event = EventData::from([
    'name'     => 'PHP Conference',
    'startsAt' => '2025-06-15',
    'isPublic' => 'yes',
]);

$event->startsAt;           // DateTime object
$event->isPublic;           // true
$event->toArray()['startsAt']; // '2025-06-15'
```

## 6. Nested DTOs

```php
class AddressData extends BaseData
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class ProfileData extends BaseData
{
    public function __construct(
        public readonly UserData $user,
        public readonly AddressData $address,
    ) {}
}

$profile = ProfileData::from([
    'user'    => ['name' => 'Alice', 'email' => 'alice@example.com'],
    'address' => ['street' => '123 Main St', 'city' => 'Kyiv'],
]);

$profile->user->name;       // 'Alice'
$profile->address->city;    // 'Kyiv'
```

## 7. Immutable Updates

```php
$updated = $user->with(email: 'newemail@example.com');

$user->email;    // 'alice@example.com' — original unchanged
$updated->email; // 'newemail@example.com'
```

## What's Next?

- [Hydration →](../features/hydration.md) — all input formats, enums, nested DTOs
- [Cast System →](../casts/index.md) — built-in and custom casts
- [Validation →](../features/validation.md) — full Laravel rule support
- [Laravel Integration →](../features/laravel.md) — `fromRequest()`, `fromModel()`, `toResponse()`
