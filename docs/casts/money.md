# MoneyCast

Casts to/from `StdOut\SimpleDataObjects\ValueObjects\Money`, an immutable value object storing the amount in **minor units** (integer cents) instead of a float — floats can't represent decimal currency amounts exactly, which silently corrupts money math over time.

The core package stays dependency-light, so `Money` is a small value object shipped in the package rather than an integration with a library like `moneyphp/money`. It covers amount + currency, `toDecimal()`, `equals()`, and currency-checked `add()`/`subtract()` — reach for a dedicated money library if you need more (allocation, exchange rates, ISO 4217-aware decimal places, ...).

## Usage

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\MoneyCast;
use StdOut\SimpleDataObjects\ValueObjects\Money;

class OrderData extends BaseData
{
    public function __construct(
        #[Cast(new MoneyCast(currency: 'USD'))]
        public readonly Money $price,
    ) {}
}

$order = OrderData::from(['price' => '10.99']);

$order->price->amount;      // 1099 (cents)
$order->price->currency;    // 'USD'
$order->price->toDecimal(); // '10.99'

$order->toArray()['price']; // 1099 — round-trips through from()
```

The currency is fixed per field via the constructor, so it isn't repeated in the serialized payload — only the minor-units integer round-trips through `toArray()`/`from()`.

## Accepted Input on Hydration

- **Int minor units** — `1099` → `$1099` treated as already-scaled cents, used as-is.
- **Decimal string** — `'10.99'` → `1099` cents (parsed and rounded using `decimals`, 2 by default).
- **Array** — `['amount' => 1099, 'currency' => 'USD']`. If `currency` is omitted it defaults to the field's configured currency; if present it must match, or an `InvalidArgumentException` is thrown.
- **`Money` instance** — passed through as-is (currency must match).

Raw `float` input is rejected (`InvalidArgumentException`) — that's the whole point of the cast.

## Custom Decimal Places

```php
#[Cast(new MoneyCast(currency: 'JPY', decimals: 0))]
public readonly Money $total,
```

`decimals` controls how decimal-string input is scaled into minor units; it has no bearing on stored data (`Money::$amount` is always a plain int).

## Currency Mismatches

Both directions validate the currency against the cast's configured value:

```php
OrderData::from(['price' => ['amount' => 1099, 'currency' => 'EUR']]);
// throws InvalidArgumentException — field is configured for USD
```

## Null Handling

Returns `null` for `null` input on both hydration and serialization.
