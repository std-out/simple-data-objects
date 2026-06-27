# Collections

## TypedDataCollection

`TypedDataCollection<T>` extends Laravel's `Collection` with generic type hints so IDEs know the element type.

### From a DTO class

```php
$collection = UserData::collection([
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob',   'email' => 'bob@example.com'],
]);

$collection->first(); // UserData — IDE infers type
$collection->last();  // UserData
$collection->count(); // 2
```

### Static factory

```php
use StdOut\SimpleDataObjects\TypedDataCollection;

$collection = TypedDataCollection::of(UserData::class, $rawItems);
```

### Pass-through existing instances

Already-hydrated instances are passed through without re-hydration:

```php
$user = UserData::from([...]);
$collection = UserData::collection([$user, [...]]);
// $collection->first() === $user
```

## Nested Collections in DTOs

Use `#[DataCollection(ClassName::class)]` to declare a property as a typed collection:

```php
use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\TypedDataCollection;

class TeamData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[DataCollection(UserData::class)]
        public readonly TypedDataCollection $members,
    ) {}
}

$team = TeamData::from([
    'name'    => 'Engineering',
    'members' => [
        ['name' => 'Alice', 'email' => 'alice@example.com'],
        ['name' => 'Bob',   'email' => 'bob@example.com'],
    ],
]);

$team->members->count();        // 2
$team->members->first()->name;  // 'Alice' — typed
```

## Collection Methods

All standard Laravel `Collection` methods are available:

```php
$collection
    ->filter(fn (UserData $u) => str_contains($u->email, '@company.com'))
    ->map(fn (UserData $u) => $u->with(phone: null))
    ->each(fn (UserData $u) => $mailer->send($u->email))
    ->toArray();
```

## Serialization

A collection in a DTO serializes each element via `toArray()`:

```php
$team->toArray();
// [
//   'name' => 'Engineering',
//   'members' => [
//     ['name' => 'Alice', 'email' => 'alice@example.com'],
//     ['name' => 'Bob',   'email' => 'bob@example.com'],
//   ],
// ]
```
