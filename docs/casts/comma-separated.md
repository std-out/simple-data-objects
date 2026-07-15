# CommaSeparatedCast

Splits a delimited string into an array on hydration, and joins the array back into a string on serialization — a common shape for query params, CSV-ish fields, and simple API list values.

## Usage

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\CommaSeparatedCast;

class FilterData extends BaseData
{
    public function __construct(
        #[Cast(new CommaSeparatedCast)]
        public readonly array $tags,

        #[Cast(new CommaSeparatedCast(separator: '|', trim: false))]
        public readonly array $raw,
    ) {}
}

$filter = FilterData::from(['tags' => 'a, b, c', 'raw' => 'x|y']);

$filter->tags; // ['a', 'b', 'c']
$filter->raw;  // ['x', 'y']

$filter->toArray()['tags']; // 'a,b,c'
```

## Behavior

- Array input passes through unchanged on hydration.
- Empty string input hydrates to `[]`.
- Items are trimmed by default (`trim: true`); pass `trim: false` to keep raw whitespace.
- Serialization (`toArray()`) joins the array back with the same separator, so `from()`/`toArray()` round-trip.

## Null Handling

Returns `null` for `null` input on both hydration and serialization.
