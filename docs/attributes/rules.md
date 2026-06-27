# #[Rules]

Declares Laravel validation rules for a constructor parameter. Rules are applied by `validate()` and `fromValidated()`.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\Rules;

#[Rules(['rule1', 'rule2', ...])]
public readonly string $property,
```

## Example

```php
use Illuminate\Validation\Rules\Password;

class RegisterData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string', 'max:100'])]
        public readonly string $name,

        #[Rules(['required', 'email:rfc,dns'])]
        public readonly string $email,

        #[Rules(['required', new Password->min(8)->letters()->numbers()])]
        public readonly string $password,

        #[Rules(['nullable', 'date_format:Y-m-d'])]
        public readonly ?string $birthday = null,
    ) {}
}
```

## Validation Methods

| Method | Behaviour |
|---|---|
| `fromValidated($data)` | Validates then hydrates. Throws `ValidationException` on failure. |
| `validate($data)` | Validates only. Throws `ValidationException` on failure, returns void. |
| `from($data)` | **No validation.** Use for trusted internal data. |

## All Rule Types Supported

Any rule type supported by Laravel works:

```php
#[Rules(['required', 'string'])]                        // string rules
#[Rules(['required', Rule::in(['a', 'b'])])]             // Rule objects
#[Rules(['required', fn ($attr, $val, $fail) => ...])]  // closures
#[Rules(['required', new CustomRule])]                   // custom Rule classes
```

::: warning File Cache
String rules are always file-cacheable. Non-string rules (Rule objects, closures) disable the file cache for that class and fall back to in-memory caching.
:::

## Nullable Fields

Always include `nullable` in rules for optional properties to avoid spurious "field is required" errors:

```php
#[Rules(['nullable', 'string', 'max:200'])]
public readonly ?string $notes = null,
```
