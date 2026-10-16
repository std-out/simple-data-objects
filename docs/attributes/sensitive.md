# #[Sensitive]

Marks a field whose value must never leak through an implicit channel: exception messages, `HydrationResult::errors()`, `serialize()`, `var_dump()` and `print_r()`. Use it for card numbers (PAN), CVV, tokens and personal data.

The value is still hydrated, readable on the object and included in `toArray()` / `toJson()` — combine it with [`#[Hidden]`](./hidden.md) to keep it out of output as well.

## Syntax

The class must use the `HasSensitiveFields` trait. A class with `#[Sensitive]` fields but without the trait throws `InvalidArgumentException` on its first hydration, so a forgotten trait can never leak silently.

```php
use StdOut\SimpleDataObjects\Attributes\Hidden;
use StdOut\SimpleDataObjects\Attributes\Sensitive;
use StdOut\SimpleDataObjects\Concerns\HasSensitiveFields;

class CardData extends BaseData
{
    use HasSensitiveFields;

    public function __construct(
        public readonly string $holder,
        #[Sensitive, Hidden]
        public readonly string $pan,
        #[Sensitive]
        public readonly CardBrand $brand,
    ) {}
}
```

## What it changes

| Channel | Behavior |
|---|---|
| Hydration errors | `from()`, `fromResult()` and `with()` report `Invalid value for sensitive field 'brand' of CardData.` — the rejected value and the cast's original exception are dropped |
| `serialize()` | Throws `LogicException` while a sensitive field is not `null`, so the value cannot reach a queue, cache or session in plaintext |
| `var_dump()` / `print_r()` | Shows `[redacted]` instead of the value |
| `toArray()` / `toJson()` | Unchanged — add `#[Hidden]` to exclude the field |

```php
$card = CardData::from(['holder' => 'Alice', 'pan' => '4111111111111111', 'brand' => 'visa']);

print_r($card);
// CardData Object ( [holder] => Alice [pan] => [redacted] [brand] => [redacted] )

serialize($card);
// LogicException: Cannot serialize CardData: field 'pan' is #[Sensitive].

CardData::fromResult(['holder' => 'Alice', 'pan' => '4111111111111111', 'brand' => '4111111111111111'])->errors();
// ['brand' => "Invalid value for sensitive field 'brand' of CardData."]
```

To queue a job that needs the card, pass an identifier (or a token from your payment provider) and load the data inside the job.

## Limits

- Symfony VarDumper (`dump()`, `dd()`) reads properties directly and ignores `__debugInfo()`. Register the caster once at boot — for example in a service provider's `boot()` — to redact `#[Sensitive]` fields there too:

  ```php
  \StdOut\SimpleDataObjects\Support\SensitiveVarDumperCaster::register();
  ```

  It requires `symfony/var-dumper` (installed with Laravel) and changes nothing until you call it.
- `var_export()` has no hook and always shows the value.
- A field without a cast, enum or nested type goes to the constructor as-is; a wrong type raises PHP's `TypeError`, which names the type, never the value.
- Every entry point (`from*()`, `tryFrom()`, `validate()`, `with()`, `collection()`), the compiled hydrators, pipes, casts and the Eloquent casts mark their input `#[\SensitiveParameter]`, so with `zend.exception_ignore_args=Off` stack traces show `Object(SensitiveParameterValue)` instead of the input. Your own casts and pipes need the same attribute on their `$value` / `$data` parameter. Keep `zend.exception_ignore_args=On` in production anyway: it also covers application frames.

## PAN masking

Independently of `#[Sensitive]`, every message built by the library — unknown keys, rejected enum and discriminator values, `UuidCast`, `MoneyCast`, `DateTimeCast` and `DateTimeImmutableCast` — and every entry of `HydrationResult::errors()` masks digit sequences that look like a card number (13–19 digits, optionally separated by spaces or dashes), keeping only the last four:

```
Unknown discriminator value '************1111' in field 'type' for PaymentMethodData.
```
