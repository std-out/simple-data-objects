# #[WhenLoaded]

Includes an Eloquent relation in `fromModel()` hydration only when that relation is actually loaded on the model. Requires [`HasLaravelIntegration`](../features/laravel.md).

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\WhenLoaded;

#[WhenLoaded('customer')]
public readonly ?CustomerData $customer = null,
```

The string is the **relation name** on the Eloquent model (`$model->customer`), not the property name.

## Example

```php
class OrderData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        public readonly int $id,
        #[WhenLoaded('customer')]
        public readonly ?CustomerData $customer = null,
    ) {}
}
```

```php
OrderData::fromModel(Order::find($id));                    // customer: null
OrderData::fromModel(Order::with('customer')->find($id));  // customer: hydrated CustomerData
```

## How it works

`fromModel()` builds its input from `$model->attributesToArray()` — the model's own columns, no relations — then, for each `#[WhenLoaded]` parameter, adds the relation only if `$model->relationLoaded($relation)` is true. An unloaded relation is simply absent from the input, so the parameter falls back to its default (or throws, if it has none and isn't nullable) — the same rule as any other missing field.

This also means `fromModel()` no longer serializes relations the DTO doesn't ask for, avoiding wasted work and accidental N+1 triggers from eager-loaded-but-unused relations.

## Collections

Works with [`#[DataCollection]`](./data-collection.md) for `hasMany`/`belongsToMany` relations — the loaded relation (an Eloquent collection) hydrates the same way any iterable would:

```php
#[WhenLoaded('items')]
#[DataCollection(ItemData::class)]
public readonly ?TypedDataCollection $items = null,
```

## Combining with #[IgnoreIfNull]

Since an unloaded relation resolves to `null` (given a nullable, defaulted property), pair it with [`#[IgnoreIfNull]`](./ignore-if-null.md) to drop it from `toArray()` entirely rather than serializing it as `null`:

```php
#[WhenLoaded('customer')]
#[IgnoreIfNull]
public readonly ?CustomerData $customer = null,
```
