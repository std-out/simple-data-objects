# Livewire Integration

The `WireableData` trait lets a data object be used as a public Livewire component property. It is **optional and fully decoupled** — this package has no dependency on `livewire/livewire`, and using `WireableData` does not pull one in.

## Setup

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\WireableData;

class OrderData extends BaseData implements \Livewire\Wireable
{
    use WireableData;

    public function __construct(
        public readonly int $id,
        public readonly string $status,
    ) {}
}
```

Two things are required, and both are your responsibility, not the trait's:

1. **`implements \Livewire\Wireable`** — Livewire checks `$value instanceof Wireable` to decide whether to call `toLivewire()`/`fromLivewire()`. The trait only supplies the two methods; PHP traits cannot declare that a class implements an interface.
2. **`livewire/livewire` installed** — needed for the `\Livewire\Wireable` interface to exist at all. This package does not require it; add it to your own application.

## How it works

`WireableData` adds exactly two methods, both delegating to the round trip you already have:

```php
public function toLivewire(): array
{
    return $this->toArray();
}

public static function fromLivewire(mixed $value): static
{
    return static::from($value);
}
```

Every property flows through the same casts as `toArray()`/`from()` — an `EnumCast`, `DateTimeCast`, or custom cast round-trips through a Livewire request exactly as it would through `toJson()`/`from()`.

```php
class Counter extends Component
{
    public OrderData $order; // hydrated/dehydrated automatically each request

    public function mount(OrderData $order): void
    {
        $this->order = $order;
    }
}
```

## Combining with Laravel Integration

`WireableData` and [`HasLaravelIntegration`](./index.md) are independent traits — use either, both, or neither on a given class:

```php
class OrderData extends BaseData implements \Livewire\Wireable
{
    use HasLaravelIntegration;
    use WireableData;
}
```
