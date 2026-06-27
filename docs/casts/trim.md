# TrimCast

Trims whitespace from string values on both hydration and serialization.

## Usage

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\TrimCast;

class ContactData extends BaseData
{
    public function __construct(
        #[Cast(new TrimCast)]
        public readonly string $name,

        #[Cast(new TrimCast)]
        public readonly string $email,
    ) {}
}

$contact = ContactData::from([
    'name'  => '  Alice  ',
    'email' => ' alice@example.com ',
]);

$contact->name;  // 'Alice'
$contact->email; // 'alice@example.com'
```

## Null Handling

Returns `null` for `null` input.

## Combining with Validation

```php
#[Cast(new TrimCast)]
#[Rules(['required', 'string', 'min:2'])]
public readonly string $name,
```

::: tip
Trim before validation to avoid "min:2" failing because of leading/trailing spaces. The cast runs during hydration which happens after validation in `fromValidated()`. If you need trimmed values during validation, trim in a middleware or request preprocessor instead.
:::
