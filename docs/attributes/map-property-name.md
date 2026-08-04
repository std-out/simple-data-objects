# #[MapPropertyName]

Maps an input key name to a different PHP property name. Useful when the API uses snake_case or kebab-case keys but you prefer camelCase PHP properties, or when the key is a PHP reserved word.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\MapPropertyName;

#[MapPropertyName('input_key')]
public readonly string $phpProperty,
```

## Example

```php
class PaymentData extends BaseData
{
    public function __construct(
        #[MapPropertyName('card_number')]
        public readonly string $cardNumber,

        #[MapPropertyName('expiry_date')]
        public readonly string $expiryDate,

        #[MapPropertyName('cvv_code')]
        public readonly string $cvv,
    ) {}
}

$payment = PaymentData::from([
    'card_number' => '4111111111111111',
    'expiry_date' => '12/27',
    'cvv_code'    => '123',
]);

$payment->cardNumber; // '4111111111111111'
$payment->cvv;        // '123'
```

## Serialization

`toArray()` uses the **input key** (the mapped name), not the PHP property name:

```php
$payment->toArray();
// [
//   'card_number' => '4111111111111111',
//   'expiry_date' => '12/27',
//   'cvv_code'    => '123',
// ]
```

## Aliases

Pass more than one name to accept several possible input keys — the first one present in the data wins:

```php
#[MapPropertyName('user_id', 'userId', 'uid')]
public readonly int $userId,
```

```php
PaymentData::from(['uid' => 42, ...]);       // accepted
PaymentData::from(['user_id' => 42, ...]);   // also accepted, takes priority if both are present
```

`toArray()` always serializes under the **first** alias (`user_id` above) — aliases beyond the first exist purely to widen what hydration accepts, they never affect output.

## Independent input/output names

`#[MapPropertyName]` always uses the same name for both directions. To map a different key for hydration than for serialization, use [`#[MapInputName]` / `#[MapOutputName]`](./map-input-output-name.md) instead — they cannot be combined with `#[MapPropertyName]` on the same property.

## Class-level Remapping

For systematic key transformation (snake_case ↔ camelCase), use [`#[TransformKeys]`](./transform-keys.md) on the class instead of mapping each property individually.
