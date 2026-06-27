# Custom Casts

Implement `CastsValue` to create your own cast.

## Interface

```php
namespace StdOut\SimpleDataObjects\Contracts;

interface CastsValue
{
    public function get(mixed $value): mixed;
    public function set(mixed $value): mixed;
}
```

- `get()` — called during **hydration** (raw input → PHP value)
- `set()` — called during **serialization** (PHP value → serializable form)

## Example: Money Cast

```php
use StdOut\SimpleDataObjects\Contracts\CastsValue;
use Money\Money;
use Money\Currency;

final class MoneyCast implements CastsValue
{
    public function __construct(
        public readonly string $currency = 'USD',
    ) {}

    public static function __set_state(array $state): self
    {
        return new self($state['currency']);
    }

    public function get(mixed $value): ?Money
    {
        if ($value === null) {
            return null;
        }

        return new Money((int) $value, new Currency($this->currency));
    }

    public function set(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return (int) (string) $value->getAmount();
    }
}
```

```php
class InvoiceData extends BaseData
{
    public function __construct(
        #[Cast(new MoneyCast('EUR'))]
        public readonly Money $total,
    ) {}
}

$invoice = InvoiceData::from(['total' => 1999]); // 1999 cents
$invoice->total->getAmount(); // '1999'
$invoice->toArray()['total']; // 1999
```

## File Cache Compatibility

To participate in the file-based metadata cache, the cast must implement `__set_state()`:

```php
public static function __set_state(array $state): self
{
    return new self($state['currency']);
}
```

::: warning
Properties referenced in `__set_state()` must be **`public readonly`**. PHP mangles private property names in `var_export()` output, making them inaccessible via `$state['propName']`.
:::

If a cast does not implement `__set_state()`, the class's metadata will fall back to the in-memory cache.

## Example: Phone Normalizer

```php
final class PhoneNormalizerCast implements CastsValue
{
    public function get(mixed $value): ?string
    {
        if ($value === null) return null;
        return preg_replace('/[^+\d]/', '', (string) $value);
    }

    public function set(mixed $value): ?string
    {
        return $value; // already normalized
    }

    public static function __set_state(array $state): self
    {
        return new self();
    }
}
```
