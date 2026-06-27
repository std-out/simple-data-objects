# BooleanCast

Casts truthy/falsy string values to PHP `bool`. Useful when consuming form data or APIs that send booleans as strings.

## Usage

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\BooleanCast;

class FormData extends BaseData
{
    public function __construct(
        #[Cast(new BooleanCast)]
        public readonly bool $acceptedTerms,

        #[Cast(new BooleanCast)]
        public readonly bool $subscribeNewsletter,
    ) {}
}
```

## Truthy values → `true`

`'1'`, `'true'`, `'yes'`, `'on'`, `1`, `true`

## Falsy values → `false`

`'0'`, `'false'`, `'no'`, `'off'`, `0`, `false`, `''`, `null`

## Example

```php
$form = FormData::from([
    'acceptedTerms'       => 'yes',   // → true
    'subscribeNewsletter' => '0',     // → false
]);

$form->acceptedTerms;       // true
$form->subscribeNewsletter; // false

$form->toArray();
// ['acceptedTerms' => true, 'subscribeNewsletter' => false]
```

## Serialization

`set()` returns the native PHP `bool` value unchanged. The output is a JSON boolean (`true`/`false`), not a string.
