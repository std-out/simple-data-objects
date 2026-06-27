# #[Flatten]

Inlines the fields of a nested DTO into the parent's `toArray()` output and reads them from the flat input during hydration.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\Flatten;

#[Flatten]
public readonly SomeData $nestedDto,
```

The property **must** be typed as a `BaseData` subclass.

## Example

```php
class AddressData extends BaseData
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
    ) {}
}

class CustomerData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[Flatten]
        public readonly AddressData $address,
    ) {}
}
```

### Hydration (flat input → nested object)

```php
$customer = CustomerData::from([
    'name'    => 'Alice',
    'street'  => '123 Main St',  // read by AddressData
    'city'    => 'Kyiv',
    'country' => 'UA',
]);

$customer->address->city; // 'Kyiv'
```

### Serialization (nested object → flat output)

```php
$customer->toArray();
// [
//   'name'    => 'Alice',
//   'street'  => '123 Main St',
//   'city'    => 'Kyiv',
//   'country' => 'UA',
// ]
```

No `address` key appears in the output — fields are merged into the parent level.

## Constraints

Cannot combine `#[Flatten]` with `#[Cast]` or `#[DataCollection]` on the same parameter. The nested class must extend `BaseData`.

## Conflict Detection

If two flattened DTOs (or a flattened DTO and a parent field) declare the same property name, the last writer wins during hydration. Design your DTOs to avoid key collisions.
