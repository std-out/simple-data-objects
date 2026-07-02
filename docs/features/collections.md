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

## Lazy Collections

`lazyCollection()` returns an `Illuminate\Support\LazyCollection` that hydrates items **one at a time as they are consumed**, instead of materializing everything upfront. Use it for large iterables — a DB cursor, a generator streaming a big CSV — where holding every hydrated instance in memory at once would be wasteful:

```php
$names = UserData::lazyCollection($csvRowGenerator)
    ->take(3)
    ->map(fn (UserData $u) => $u->name)
    ->all();
// only 3 rows were ever hydrated, no matter how large the source is
```

Like `collection()`, already-hydrated instances pass through unchanged.

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
