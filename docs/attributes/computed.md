# #[Computed]

Adds a derived, method-backed field to serialization output. Useful for values that are combinations of other properties and don't need their own constructor parameter.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\Computed;

#[Computed]
public function fullName(): string
{
    return "{$this->firstName} {$this->lastName}";
}
```

`#[Computed]` goes on a public, non-static method that takes no required parameters. It works on constructor-based, constructor-less, and hybrid classes alike.

## Example

```php
class PersonData extends BaseData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}

    #[Computed]
    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}

$person = PersonData::from(['firstName' => 'Ada', 'lastName' => 'Lovelace']);

$person->toArray();
// ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'fullName' => 'Ada Lovelace']
```

The return value goes through the same normalization as any other field — a nested `BaseData`, enum, or `Collection` returned from a computed method serializes correctly, not just scalars.

## Output key

By default the key is the method name. Override it with `#[Computed('full_name')]`. A class-level [`#[TransformKeys]`](./transform-keys.md) strategy applies to the default key the same way it applies to properties.

## Hydration

Computed keys are never read back on input — `from()` simply ignores them, so `from($dto->toArray())` always works. Under [`#[RejectUnknownKeys]`](./reject-unknown-keys.md), a computed key is treated as known, so it doesn't trip the unknown-key check on a roundtrip.
