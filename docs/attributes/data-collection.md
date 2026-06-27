# #[DataCollection]

Declares that a property holds a typed collection of another `BaseData` class. The property type must be `TypedDataCollection`.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\TypedDataCollection;

#[DataCollection(ItemData::class)]
public readonly TypedDataCollection $items,
```

## Example

```php
class InvoiceData extends BaseData
{
    public function __construct(
        public readonly string            $number,
        #[DataCollection(LineItemData::class)]
        public readonly TypedDataCollection $lines,
    ) {}
}

$invoice = InvoiceData::from([
    'number' => 'INV-001',
    'lines'  => [
        ['description' => 'Widget', 'quantity' => 2, 'price' => 9.99],
        ['description' => 'Gadget', 'quantity' => 1, 'price' => 49.00],
    ],
]);

$invoice->lines->count();              // 2
$invoice->lines->first()->description; // 'Widget'
```

## Serialization

```php
$invoice->toArray();
// [
//   'number' => 'INV-001',
//   'lines'  => [
//     ['description' => 'Widget', 'quantity' => 2, 'price' => 9.99],
//     ['description' => 'Gadget', 'quantity' => 1, 'price' => 49.00],
//   ],
// ]
```

## Constraints

Cannot combine with `#[Cast]` or `#[Flatten]` on the same parameter.

## See Also

- [Collections feature →](/features/collections)
- [`TypedDataCollection` generics →](/features/collections#typeddatacollection)
