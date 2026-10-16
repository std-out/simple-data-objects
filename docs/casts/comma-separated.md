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

## Limiting the number of items

Every item costs far more memory than its separator: a 10 MB string of commas expands to about 500 MB of array, and running out of memory is a fatal error that `tryFrom()` cannot catch. For untrusted input, cap the count:

```php
#[Cast(new CommaSeparatedCast(maxItems: 100))]
public readonly array $tags,
```

Input with more items — a string or an array — throws `InvalidArgumentException`, and the string is never split past the limit. Without `maxItems` there is no limit.

## Null Handling

Returns `null` for `null` input on both hydration and serialization.
