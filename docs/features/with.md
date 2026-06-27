# Immutable Copies — with()

`with()` returns a new instance with the specified fields overridden. The original object is **never mutated** — all properties are `readonly`.

## Basic Usage

Use named arguments to specify which fields to override:

```php
$original = UserData::from([
    'name'  => 'Alice',
    'email' => 'alice@example.com',
]);

$updated = $original->with(email: 'alice@new.com');

$original->email; // 'alice@example.com' — unchanged
$updated->email;  // 'alice@new.com'
$updated->name;   // 'Alice' — preserved
```

## Multiple Fields

```php
$updated = $user->with(
    name:  'Bob',
    email: 'bob@example.com',
    phone: '+380991234567',
);
```

## Chaining

```php
$result = $user
    ->with(name: 'Bob')
    ->with(email: 'bob@example.com')
    ->with(phone: '123');
```

## Casts Apply to Overrides

When overriding a field that has a `#[Cast]`, the cast is applied to the new value exactly as in `from()`:

```php
$event = EventData::from(['name' => 'Conf', 'startsAt' => '2024-01-01']);

$updated = $event->with(startsAt: '2025-06-15');

$updated->startsAt; // DateTime('2025-06-15') — cast applied
```

If you pass an already-cast value, it is used directly:

```php
$dt = new DateTime('2025-06-15');
$updated = $event->with(startsAt: $dt);
$updated->startsAt === $dt; // true
```

## Pattern: Copy-on-Write

`with()` is ideal for "update state" patterns without mutable setters:

```php
function applyPromotion(OrderData $order, float $discount): OrderData
{
    return $order->with(
        totalPrice: $order->totalPrice * (1 - $discount),
        hasPromotion: true,
    );
}
```

## How it Works

`with()` reads the current object's properties via `get_object_vars()`, applies overrides through the same `ValueCaster` pipeline used by `from()`, and constructs a new instance via the constructor. No reflection at call time — metadata is already cached.
