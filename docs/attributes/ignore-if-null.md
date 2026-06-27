# #[IgnoreIfNull]

Omits a property from `toArray()` / `toJson()` output when its value is `null`. When the value is non-null, it appears normally.

## Syntax

```php
use StdOut\SimpleDataObjects\Attributes\IgnoreIfNull;

#[IgnoreIfNull]
public readonly ?string $optionalField = null,
```

## Example

```php
class ArticleData extends BaseData
{
    public function __construct(
        public readonly string  $title,
        public readonly string  $body,
        #[IgnoreIfNull]
        public readonly ?string $subtitle = null,
        #[IgnoreIfNull]
        public readonly ?string $imageUrl = null,
    ) {}
}

ArticleData::from(['title' => 'Hello', 'body' => 'World'])->toArray();
// ['title' => 'Hello', 'body' => 'World']  — subtitle and imageUrl absent

ArticleData::from(['title' => 'Hello', 'body' => 'World', 'subtitle' => 'Sub'])->toArray();
// ['title' => 'Hello', 'body' => 'World', 'subtitle' => 'Sub']  — imageUrl absent
```

## vs. #[Hidden]

| | `#[Hidden]` | `#[IgnoreIfNull]` |
|---|---|---|
| Always omitted | Yes | No — only when null |
| Accessible on object | Yes | Yes |
| Non-null values in output | N/A | Yes |

Use `#[Hidden]` for properties that should **never** appear in output. Use `#[IgnoreIfNull]` for optional properties that should appear only when set.
