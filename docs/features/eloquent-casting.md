# Eloquent Attribute Casting

`IsEloquentCastable` and `AsDataCollection` let a data object be assigned directly as an Eloquent attribute cast, so a JSON column hydrates straight into a DTO (or a typed collection of them) instead of a plain array. Both are **optional and fully decoupled** — this package has no dependency on `illuminate/database`, and neither is touched unless you opt in.

## Single data object

```php
use Illuminate\Contracts\Database\Eloquent\Castable;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\IsEloquentCastable;

class AddressData extends BaseData implements Castable
{
    use IsEloquentCastable;

    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}
```

```php
class Order extends Model
{
    protected function casts(): array
    {
        return ['address' => AddressData::class];
    }
}

$order->address;                       // AddressData, hydrated from the JSON column
$order->address = ['street' => ..., 'city' => ...]; // plain array is hydrated too
$order->address = new AddressData(...);             // or assign an instance directly
$order->save();                        // stored as JSON via toJson()
```

On a Laravel version without the `casts()` method (10.x), use the `$casts` property instead — the mapping is identical either way:

```php
protected $casts = ['address' => AddressData::class];
```

## Collections

`AsDataCollection::of()` casts a JSON array column to a `TypedDataCollection`, mirroring Laravel's own `AsCollection::using()` idiom. The item class needs no trait or interface — only `AsDataCollection` itself is `Castable`:

```php
use StdOut\SimpleDataObjects\Laravel\AsDataCollection;

class Order extends Model
{
    protected function casts(): array
    {
        return ['items' => AsDataCollection::of(ItemData::class)];
    }
}

$order->items;      // TypedDataCollection<ItemData>
$order->items[0]->sku;
```

In the `$casts` property style (Laravel 10.x), write the same string `of()` builds under the hood:

```php
protected $casts = ['items' => AsDataCollection::class.':'.ItemData::class];
```

## How the decoupling works

Two things are required, and both are your responsibility, not the trait's:

1. **`implements \Illuminate\Contracts\Database\Eloquent\Castable`** — Eloquent checks `is_subclass_of($castType, Castable::class)` before it will call `castUsing()`. The trait only supplies the method; PHP traits cannot declare that a class implements an interface.
2. **`illuminate/database` installed** — needed for `\Illuminate\Database\Eloquent\Model` (referenced by the actual caster classes) to exist. This package does not require it; add it to your own application, which you already have if you're using Eloquent.

The actual caster classes — `StdOut\SimpleDataObjects\Laravel\DataObjectCast` and `DataCollectionCast` — live under a dedicated `Laravel\` namespace and are only autoloaded when `castUsing()` actually runs, i.e. only inside a real Eloquent model. Nothing under `Laravel\` is loaded, or needs `illuminate/database` present, for standalone or non-Laravel usage of this package.

## Dirty-tracking

Eloquent normally checks whether an attribute changed by comparing the raw stored value byte-for-byte, which would make a freshly re-serialized DTO look "dirty" any time its JSON came out differently ordered than what's already stored — even when nothing meaningfully changed. Both casters define a `compare()` method that Eloquent picks up automatically (via `method_exists()`, not an interface) to avoid that: `isDirty()` decodes both sides and compares through the DTO's own `equals()` (or item-by-item for collections), so only an actual data change is reported as dirty.

This requires **Laravel 12+** — the dirty-check dispatch to `compare()` doesn't exist in Eloquent before that. On 10.x/11.x, `isDirty()` falls back to Eloquent's default raw-byte comparison, so a value can occasionally show up as dirty even when unchanged; single-object and collection casting themselves work the same on every supported version (10–13).

## Null and invalid data

A `null` column round-trips to `null` in both directions. Invalid JSON already stored in the column throws `DataHydrationException` the moment the attribute is accessed — the same exception `from()` throws for invalid JSON anywhere else in this package.

## Combining with a `#[Discriminator]` base class

`AddressData::class` in `casts()` can be an abstract [`#[Discriminator]`](../attributes/discriminator.md) class — `DataObjectCast` hydrates through `from()`, which already dispatches to the right concrete subclass.
