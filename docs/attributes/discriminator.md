# #[Discriminator]

Declares **polymorphic hydration** on an abstract data class: `from()` reads a discriminator field from the input and delegates to the concrete subclass mapped to that value — the same idea as Rust serde's tagged enums, pydantic's discriminated unions, or Jackson's `@JsonTypeInfo`.

Resolution happens inside the compiled hydrator: one array lookup, then the target class's own (cached) compiled hydrator takes over. No reflection at runtime.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\Discriminator;

#[Discriminator(
    field: 'type',                       // input key to read
    map: [                               // value => concrete class
        'card' => CardPaymentData::class,
        'bank' => BankPaymentData::class,
    ],
    fallback: OtherPaymentData::class,   // optional
)]
abstract class PaymentMethodData extends BaseData {}
```

The class **must** be abstract, and every class in `map` (and `fallback`) **must** extend it. Both constraints — plus map emptiness and class existence — are checked once, when the class metadata is built, so a misconfiguration fails immediately rather than on some future request.

## Example

```php
#[Discriminator(field: 'type', map: [
    'card' => CardPaymentData::class,
    'bank' => BankPaymentData::class,
])]
abstract class PaymentMethodData extends BaseData {}

class CardPaymentData extends PaymentMethodData
{
    public function __construct(
        public readonly int $amount,
        public readonly string $last4,
        public readonly string $type = 'card',
    ) {}
}

class BankPaymentData extends PaymentMethodData
{
    public function __construct(
        public readonly int $amount,
        public readonly string $iban,
        public readonly string $type = 'bank',
    ) {}
}
```

```php
$payment = PaymentMethodData::from(['type' => 'card', 'amount' => 100, 'last4' => '4242']);
// => CardPaymentData

$payment = PaymentMethodData::from('{"type":"bank","amount":250,"iban":"UA90..."}');
// => BankPaymentData
```

Every `BaseData` entry point dispatches:

| Entry point | Behavior |
|---|---|
| `from()` / `tryFrom()` / `fromJson()` | Resolves the subclass, hydrates it |
| `fromLazy()` | Resolves the subclass **eagerly** (a lazy ghost's class is fixed at creation), then defers hydration as usual |
| `fromValidated()` / `validate()` | Resolves first, then applies the **concrete subclass's** `#[Rules]` |
| `collection()` / `lazyCollection()` | Resolves per item — one collection may hold mixed subclasses |

## Round-tripping

Declare the discriminator field as a regular property on each subclass (with a default, as above) so it appears in `toArray()` — then `from(toArray())` always reconstructs the right subclass:

```php
$restored = PaymentMethodData::from($payment->toArray());
$restored->equals($payment); // true
```

## Nested and collection properties

A property typed as the abstract base dispatches automatically, including inside `#[DataCollection]`:

```php
class OrderData extends BaseData
{
    public function __construct(
        public readonly PaymentMethodData $payment,
        #[DataCollection(PaymentMethodData::class)]
        public readonly TypedDataCollection $refunds,
    ) {}
}
```

## Fallback

Without `fallback`, a missing or unmapped discriminator value throws a `DataHydrationException`. With it, the fallback class receives the input instead:

```php
#[Discriminator(
    field: 'channel',
    map: ['email' => EmailChannelData::class],
    fallback: GenericChannelData::class,
)]
abstract class ChannelData extends BaseData {}

ChannelData::from(['channel' => 'sms', 'payload' => '...']); // => GenericChannelData
```

## Details

- **Map keys** may be strings or integers. A `BackedEnum` input value is matched by its backing value.
- **Multi-level hierarchies** work: a map target may itself be an abstract `#[Discriminator]` class, dispatching further down (`ThingData` → `VehicleData` → `BikeData`).
- **Class-level `#[Pipe]`** cannot be combined with `#[Discriminator]` — declare pipes on the concrete subclasses instead. (`#[TransformKeys]` and all parameter-level attributes belong to the subclasses anyway, since PHP class attributes are not inherited.)
- The metadata file cache persists the compiled dispatcher like any other hydrator — warmed processes skip `eval` entirely.
