# Comparison — equals() & diff()

## equals()

Returns `true` if both DTOs produce the same `toArray()` output:

```php
$a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
$b = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
$c = UserData::from(['name' => 'Bob',   'email' => 'bob@example.com']);

$a->equals($b); // true
$a->equals($c); // false
$a->equals($a); // true (reflexive)
```

Comparison is **value-based**, not reference-based:

```php
$a === $b;      // false — different objects
$a->equals($b); // true  — same data
```

Objects of **different classes are never equal**, even if their `toArray()` output happens to match:

```php
$user  = UserData::from(['name' => 'Alice']);
$admin = AdminData::from(['name' => 'Alice']);

$user->equals($admin); // false — different classes
```

## diff()

Returns an array of fields that differ between two DTOs. Each entry is `[this_value, other_value]`:

```php
$a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
$b = UserData::from(['name' => 'Bob',   'email' => 'alice@example.com', 'phone' => '123']);

$a->diff($b);
// [
//   'name'  => ['Alice', 'Bob'],
//   'phone' => [null, '123'],
// ]
```

An empty array means the objects are equal:

```php
$a->diff($b) === []; // equivalent to $a->equals($b)
```

## Diff on Casted Fields

`diff()` compares serialized values (output of `toArray()`), so casts are applied before comparison:

```php
$a = EventData::from(['name' => 'Conf', 'startsAt' => '2024-01-01']);
$b = EventData::from(['name' => 'Conf', 'startsAt' => '2025-06-15']);

$a->diff($b);
// ['startsAt' => ['2024-01-01', '2025-06-15']]
```

## Use Cases

```php
// Audit log: record what changed
$before = UserData::fromModel($user);
$user->update($attributes);
$after = UserData::fromModel($user->fresh());

$changes = $before->diff($after);
AuditLog::record($user->id, $changes);
```

```php
// Guard against no-op updates
if ($existing->equals($incoming)) {
    return; // nothing changed
}
$repository->save($incoming);
```
